<?php

namespace Mecha\Modular;

use function array_key_exists;

/**
 * A key converting module decorator that simply adds a prefix to each factory key.
 *
 * @see KeyConvertingModule
 */
class PrefixedModule extends KeyConvertingModule
{
    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface $module
     * @param string          $prefix
     */
    public function __construct(ModuleInterface $module, string $prefix)
    {
        $factories = $module->getFactories();
        $callback = function ($key) use ($prefix, $factories) {
            return array_key_exists($key, $factories)
                ? $prefix . $key
                : $key;
        };

        parent::__construct($module, $callback);
    }
}
