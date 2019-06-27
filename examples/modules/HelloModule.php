<?php

namespace Mecha\Modular\Example\modules;

use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Services\Config;
use Mecha\Modular\Services\StringConfig;
use Psr\Container\ContainerInterface;

/**
 * A simple module that just echoes a hello greeting.
 */
class HelloModule implements ModuleInterface
{
    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c)
    {
        echo $c->get('hello/message') . PHP_EOL;
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
            'hello/message' => new StringConfig('Hello there, {name}', [
                // the {name} placeholder is the "hello/name" service
                'name' => 'hello/name'
            ]),

            // The name of the person to address in the greeting
            'hello/name' => new Config('admin'),
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
