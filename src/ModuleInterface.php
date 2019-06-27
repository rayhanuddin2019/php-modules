<?php

namespace Mecha\Modular;

use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface;

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
