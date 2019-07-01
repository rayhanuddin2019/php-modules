<?php

namespace Mecha\Modular\Containers;

use Psr\Container\ContainerInterface;
use function array_key_exists;

/**
 * A simple container decorator that caches the results of the inner container.
 *
 * @since [*next-version*]
 */
class CachingContainer implements ContainerInterface
{
    /**
     * @since [*next-version*]
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @since [*next-version*]
     *
     * @var array
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->cache = [];
    }

    public function get($id)
    {
        if (!array_key_exists($id, $this->cache)) {
            $this->cache[$id] = $this->container->get($id);
        }

        return $this->cache[$id];
    }

    public function has($id)
    {
        return $this->container->has($id);
    }
}
