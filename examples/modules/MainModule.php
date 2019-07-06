<?php

namespace Mecha\Modular\Example\modules;

use Mecha\Modular\ModuleInterface;
use Mecha\Modular\Services\Value;
use Mecha\Modular\Services\Service;
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
            'name/pattern' => new Value('the time in Tokyo right now is %s'),
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
            'hello_time/name' => new Service(['name/pattern', 'jap_time/time'], 'sprintf'),
        ];
    }
}
