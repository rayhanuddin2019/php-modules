<?php

namespace Mecha\Modular\Example\modules;

use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Services\Config;
use Mecha\Modular\Services\StringConfig;
use Psr\Container\ContainerInterface;

/**
 * Identical to the {@link HelloModule}, but does not prefix its keys.
 */
class GreetingModule implements ModuleInterface
{
    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c)
    {
        echo $c->get('message') . PHP_EOL;
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getFactories()
    {
        return [
            // The greeting message
            'message' => new StringConfig('Hello there, {name}', [
                // the {name} placeholder is the "hello/name" service
                'name' => 'name'
            ]),

            // The name of the person to address in the greeting
            'name' => new Config('admin'),
        ];
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getExtensions()
    {
        return [];
    }
}
