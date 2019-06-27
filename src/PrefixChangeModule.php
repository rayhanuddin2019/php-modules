<?php

namespace Mecha\Modular;

use function strlen;
use function strpos;
use function substr;

class PrefixChangeModule extends KeyConvertingModule implements ModuleInterface
{
    /**
     * @var ModuleInterface
     */
    protected $module;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface $module
     * @param string          $prefix
     * @param string          $replace
     */
    public function __construct(ModuleInterface $module, string $prefix, string $replace)
    {
        $callback = function ($key) use ($prefix, $replace) {
            if (strpos($key, $prefix) === 0) {
                return $replace . substr($key, strlen($prefix));
            }

            return $key;
        };

        parent::__construct($module, $callback);
    }
}
