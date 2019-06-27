<?php

namespace Mecha\Modular\Containers;

use ArrayAccess;
use Exception;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use function array_key_exists;
use function call_user_func_array;
use function is_callable;

class ServiceProviderContainer implements ContainerInterface
{
    /**
     * @since [*next-version*]
     *
     * @var callable[]|ArrayAccess
     */
    protected $factories;

    /**
     * @since [*next-version*]
     *
     * @var callable[]|ArrayAccess
     */
    protected $extensions;

    /**
     * @var callable
     */
    protected $parentCb;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ServiceProviderInterface $provider The provider.
     * @param callable|null            $parentCb The callback that provides the parent container. The callback
     *                                           receives this instance and the requested key as arguments.
     */
    public function __construct($provider, $parentCb = null)
    {
        $this->factories = $provider->getFactories();
        $this->extensions = $provider->getExtensions();
        $this->parentCb = $parentCb;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function get($id)
    {
        if (!$this->has($id)) {
            throw new class extends Exception implements NotFoundExceptionInterface
            {
            };
        }

        $c = is_callable($this->parentCb)
            ? call_user_func_array($this->parentCb, [$this, $id])
            : $this;

        $factory = $this->factories[$id];
        $value = call_user_func_array($factory, [$c]);

        if (array_key_exists($id, $this->extensions)) {
            return call_user_func_array($this->extensions[$id], [$c, $value]);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function has($id)
    {
        return array_key_exists($id, $this->factories);
    }
}
