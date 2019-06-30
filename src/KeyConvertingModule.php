<?php

namespace Mecha\Modular;

use Mecha\Modular\Containers\KeyConvertingContainer;
use Psr\Container\ContainerInterface;
use function array_map;
use function call_user_func_array;

/**
 * A module decorator that can change the factory keys of the inner module using a callback.
 * Internal references to factories are only detected if using the service helper classes such as {@link Factory}.
 */
class KeyConvertingModule
{
    /**
     * @var ModuleInterface
     */
    protected $module;

    /**
     * @var callable[]
     */
    protected $callback;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface $module
     * @param callable        $callback
     */
    public function __construct(ModuleInterface $module, callable $callback)
    {
        $this->module = $module;
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c)
    {
        return $this->module->run(new KeyConvertingContainer($c, $this->callback));
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getFactories()
    {
        $newFactories = [];

        foreach ($this->module->getFactories() as $origKey => $origFactory) {
            $newKey = $this->getNewKey($origKey);
            $newFac = $this->getNewFactory($origFactory);

            $newFactories[$newKey] = $newFac;
        }

        return $newFactories;
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getExtensions()
    {
        $extensions = [];

        foreach ($this->module->getExtensions() as $origKey => $origFactory) {
            $newExt = $this->getNewFactory($origFactory);

            $extensions[$origKey] = $newExt;
        }

        return $extensions;
    }

    protected function getNewFactory($arg)
    {
        if ($arg instanceof Service) {
            $newFn = clone $arg;
            $newFn->deps = $this->getNewKeyList($arg->deps);
            $newFn->factory = $this->getNewFactory($arg->factory);

            return $newFn;
        }

        return $arg;
    }

    protected function getNewKeyList($list)
    {
        return array_map([$this, 'getNewKey'], $list);
    }

    protected function getNewKey($key)
    {
        return call_user_func_array($this->callback, [$key]);
    }
}
