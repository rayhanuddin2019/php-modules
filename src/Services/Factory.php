<?php

namespace Mecha\Modular\Services;

use function call_user_func_array;
use Psr\Container\ContainerInterface;
use function array_map;

/**
 * A service helper that declares services that it depends on prior to invocation.
 *
 * Instances of this class have their dependencies accessible from the outside. The internal callback will receive those
 * service instances instead of the full container. This makes service factories cleaner and also exposes the
 * relationship between them, available for inspection, manipulation or error detection (such as circular dependency)
 * or optimization.
 */
class Factory
{
    /**
     * @var StringConfig[]
     */
    public $deps;

    /**
     * @var callable
     */
    public $callback;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param StringConfig[] $deps
     * @param callable       $callback
     */
    public function __construct(array $deps, callable $callback)
    {
        $this->deps = $deps;
        $this->callback = $callback;
    }

    /**
     * @param ContainerInterface $c
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $c)
    {
        return call_user_func_array($this->callback, $this->getArgs($c));
    }

    /**
     * @param ContainerInterface $c
     *
     * @return array
     */
    protected function getArgs(ContainerInterface $c)
    {
        return array_map(
            function ($key) use ($c) {
                return ($key === 'c') ? $c : $c->get($key);
            },
            $this->deps
        );
    }
}
