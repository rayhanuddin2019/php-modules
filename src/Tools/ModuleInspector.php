<?php

namespace Mecha\Modular\Tools;

use Exception;
use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Services\Service;
use function array_flip;
use function array_key_exists;
use function array_unshift;
use function implode;
use function preg_match_all;
use ReflectionClass;
use function serialize;
use function sha1;
use function strpos;
use function substr;

/**
 * A basic module linter. Can detect unused services, circular dependencies and references to non-existing dependencies.
 *
 * @since [*next-version*]
 */
class ModuleInspector
{
    /**
     * @since [*next-version*]
     *
     * @var array
     */
    protected $refsIgnore = [];

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param array $useIgnore Service keys that should be ignored when inspecting for unused services.
     */
    public function __construct(array $useIgnore = [])
    {
        $this->refsIgnore = $useIgnore;
    }

    public function inspect(ModuleInterface $module)
    {
        $graph = $this->buildGraph($module);
        $results = $this->analyzeGraph($graph);

        return $results;
    }

    protected function buildGraph(ModuleInterface $module)
    {
        $graph = [
            'deps' => [],
            'refs' => [],
        ];

        try {
            $refsInRun = $this->getServicesUsedInRun($module);
        } catch (Exception $exception) {
            $refsInRun = [];
        }

        foreach (array_merge($this->refsIgnore, $refsInRun) as $key) {
            $graph['refs'][$key] = 1;
        }

        foreach ($module->getFactories() as $key => $factory) {
            $graph['deps'][$key] = [];

            if (!$factory instanceof Service) {
                continue;
            }

            $graph['deps'][$key] = array_flip($factory->deps);

            foreach ($factory->deps as $dep) {
                $graph['refs'][$dep] = array_key_exists($dep, $graph['refs'])
                    ? $graph['refs'][$dep] + 1
                    : 1;
            }
        }

        return $graph;
    }

    protected function analyzeGraph($graph)
    {
        $errors = [];
        $warnings = [];

        // Holds hashes of circular dependency chains that were reported to avoid reporting them multiple times
        $circular = [];

        foreach ($graph['deps'] as $key => $deps) {
            // UNUSED SERVICE CHECK
            {
                if (!array_key_exists($key, $graph['refs']) || $graph['refs'] === 0) {
                    $warnings[] = "Service '$key' is not used";
                }
            }

            foreach ($deps as $dep => $i) {
                // UNRESOLVED DEPENDENCY CHECK
                {
                    if (!array_key_exists($dep, $graph['deps'])) {
                        $errors[] = "Service '$key' has an unresolved dependency: '$dep'";
                        continue;
                    }
                }

                // CIRCULAR DEPENDENCY CHECK
                {
                    // Check if the service is a dependency of its dependency
                    $chain = $this->getDependencyChain($graph, $dep, $key);
                    if (!empty($chain)) {
                        // Add the service to the circular chain and hash it
                        array_unshift($chain, $key);
                        $hash = $this->getDepChainHash($chain);

                        // Check if this circular chain was already reported
                        if (!isset($circular[$hash])) {
                            $circular[$hash] = true;

                            $errors[] = sprintf(
                                'Circular dependency detected: %s -> %s',
                                implode(' -> ', $chain),
                                $key
                            );
                        }
                    }
                }
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function getServicesUsedInRun(ModuleInterface $module)
    {
        // Get the reflection instance for the module's run method
        $reflect = new ReflectionClass($module);
        $run = $reflect->getMethod('run');

        // Obtain the code for the method
        $filename = $run->getFileName();
        $startLine = $run->getStartLine() - 1;
        $length = $run->getEndLine() - $startLine;
        $source = file($filename);
        $fullCode = implode('', array_slice($source, $startLine, $length));

        // Extract only the code between the braces
        $subStrStart = strpos($fullCode, '{') + 1;
        $subStrEnd = strpos($fullCode, '}');
        $code = trim(substr($fullCode, $subStrStart, $subStrEnd - $subStrStart));

        // Generate a regex that matches "$c->get()" calls, such that the "$c" is the method's arg name
        $cArg = $run->getParameters()[0]->getName();
        $regex = sprintf('/\$%s\-\>get\(\'([^\']*)\'\)/', $cArg);

        preg_match_all($regex, $code, $refs);

        return $refs[1];
    }

    /**
     * Retrieves the dependency chain between two services.
     *
     * @since [*next-version*]
     *
     * @param array  $graph   The graph.
     * @param string $start   The key of the service to start from.
     * @param string $end     The key of the dependency to end from.
     * @param array  $visited An array of visited services during deep recursive lookup.
     *
     * @return array An array of dependency service keys that make $end a dependency of $start.
     */
    protected function getDependencyChain($graph, $start, $end, &$visited = [])
    {
        // Check if a previous recursive deep search has already visited the "start" key
        if (array_key_exists($start, $visited)) {
            return [];
        }

        if (!array_key_exists($start, $graph['deps'])) {
            // The "start" service has no dependencies, so there is no chain
            return [];
        }

        if (array_key_exists($end, $graph['deps'][$start])) {
            // The "start" service depends on the "end" service, so the chain is only 1 segment long
            return [$start];
        }

        // Mark "start" as visited to avoid infinite loops in the event of circular dependency
        $visited[$start] = true;

        // Deep checking - check if there's a chain from a dependency of the "start" service to the "end" service
        foreach ($graph['deps'][$start] as $dep => $i) {
            // Get the chain from the dependency to the "end" service
            $chain = $this->getDependencyChain($graph, $dep, $end, $visited);
            // Mark the dependency as visited to avoid infinite loops in the event of circular dependency
            $visited[$dep] = true;

            // If no chain, continue to the next dependency
            if (empty($chain)) {
                continue;
            }

            // Otherwise, add "start" to the dependency's returned chain
            array_unshift($chain, $start);

            // Return the chain
            return $chain;
        }

        return [];
    }

    protected function getDepChainHash($chain)
    {
        sort($chain);

        return sha1(serialize($chain));
    }
}
