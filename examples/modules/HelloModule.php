<?php

namespace Mecha\Modular\Example\modules;

use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Services\Callback;
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
        $c->get('run')();
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
                // the {name} placeholder is the "name" service
                'name' => 'name'
            ]),

            // The name of the person to address in the greeting
            'name' => new Config('admin'),

            // The function to invoke the hello greeting
            'run' => new Callback(['message'], function ($message) {
                echo $message . PHP_EOL;
            }),
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
