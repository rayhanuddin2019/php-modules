<?php

namespace Mecha\Modular\Example\modules;

use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Service;
use Mecha\Modular\Services\Alias;
use Mecha\Modular\Services\Config;
use Psr\Container\ContainerInterface;

/**
 * The main module for our app - mainly provides application-specific factories and extensions.
 */
class MainModule implements ModuleInterface
{
    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c)
    {
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getFactories()
    {
        return [
            // The printf-style pattern to use for the greeting name replacement
            'main/name/pattern' => new Config('the time in Tokyo right now is %s'),
        ];
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     */
    public function getExtensions()
    {
        return [
            // Changes the greeting name in the hello module to the current Tokyo time in Japan
            'hello_time/name' => new Factory(['jap_time/time', 'main/name/pattern'], function ($time, $pattern) {
                return sprintf($pattern, $time);
            }),
        ];
    }
}
