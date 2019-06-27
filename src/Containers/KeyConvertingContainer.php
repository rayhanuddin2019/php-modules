<?php

namespace Mecha\Modular\Containers;

use Psr\Container\ContainerInterface;
use function call_user_func_array;

/**
 * A container decorator for converting input keys prior to delegating to the inner container.
 *
 * This class is mostly used as a companion for the {@link KeyConvertingModule} class.
 */
class KeyConvertingContainer implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var string
     */
    protected $callback;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ContainerInterface $container
     * @param callable           $callback
     */
    public function __construct(ContainerInterface $container, callable $callback)
    {
        $this->container = $container;
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function get($key)
    {
        return $this->container->get($this->getActualKey($key));
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function has($key)
    {
        return $this->container->get($this->getActualKey($key));
    }

    protected function getActualKey($key)
    {
        return call_user_func_array($this->callback, [$key]);
    }
}
