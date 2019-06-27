<?php

namespace Mecha\Modular;

use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

/**
 * Represents a module - something that can provide services, extensions and be run.
 */
interface ModuleInterface extends ServiceProviderInterface
{
    /**
     * Runs the module.
     *
     * @since [*next-version*]
     *
     * @param ContainerInterface $c A services container.
     */
    public function run(ContainerInterface $c);
}
