<?php

namespace Mecha\Modular;

use Mecha\Modular\Containers\KeyConvertingContainer;
use Mecha\Modular\Services\Alias;
use Mecha\Modular\Services\Extension;
use Mecha\Modular\Services\Factory;
use Mecha\Modular\Services\StringConfig;
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
            $newKey = $this->convertKey($origKey);
            $newFac = $this->convertFactory($origFactory);

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
            $newExt = $this->convertFactory($origFactory);

            $extensions[$origKey] = $newExt;
        }

        return $extensions;
    }

    protected function convertFactory($arg)
    {
        if ($arg instanceof Factory || $arg instanceof Extension) {
            $newFn = clone $arg;
            $newFn->deps = $this->convertKeyList($arg->deps);
            $newFn->callback = $this->convertFactory($arg->callback);

            return $newFn;
        }

        if ($arg instanceof Alias) {
            return $this->convertFactory($arg->default);
        }

        if ($arg instanceof StringConfig) {
            $newStr = clone $arg;
            $newStr->deps = $this->convertKeyList($arg->deps);

            return $newStr;
        }

        return $arg;
    }

    protected function convertKeyList($list)
    {
        return array_map([$this, 'convertKey'], $list);
    }

    protected function convertKey($key)
    {
        return call_user_func_array($this->callback, [$key]);
    }
}
