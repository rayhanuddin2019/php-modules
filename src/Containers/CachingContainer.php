<?php

namespace Mecha\Modular\Containers;

use function array_key_exists;
use Psr\Container\ContainerInterface;

class CachingContainer implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
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
