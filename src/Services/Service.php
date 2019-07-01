<?php

namespace Mecha\Modular\Services;

use Psr\Container\ContainerInterface;
use function array_map;
use function call_user_func_array;

/**
 * A service helper that declares services that it depends on prior to invocation.
 *
 * Instances of this class have their dependencies accessible from the outside. The internal callback will receive those
 * service instances instead of the full container. This makes service factories cleaner and also exposes the
 * relationship between them, available for inspection, manipulation or error detection (such as circular dependency)
 * or optimization.
 */
class Service
{
    /**
     * @var string[]
     */
    public $deps;

    /**
     * @var callable
     */
    public $factory;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string[] $deps
     * @param callable $callback
     */
    public function __construct(array $deps, callable $callback)
    {
        $this->deps = $deps;
        $this->factory = $callback;
    }

    /**
     * @param ContainerInterface $c
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $c)
    {
        return call_user_func_array($this->factory, $this->getDeps($c));
    }

    /**
     * @param ContainerInterface $c
     *
     * @return array
     */
    protected function getDeps(ContainerInterface $c)
    {
        return array_map([$c, 'get'], $this->deps);
    }
}
