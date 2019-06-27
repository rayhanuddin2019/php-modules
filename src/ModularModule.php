<?php

namespace Mecha\Modular;

use Generator;
use Psr\Container\ContainerInterface;
use Traversable;

class ModularModule implements ModuleInterface
{
    /**
     * @since [*next-version*]
     *
     * @var ModuleInterface[]|Traversable
     */
    protected $modules;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param ModuleInterface[]|Traversable $modules The modules.
     */
    public function __construct($modules)
    {
        $this->modules = $modules;
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function run(ContainerInterface $c)
    {
        foreach ($this->modules as $module) {
            $module->run($c);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getFactories()
    {
        /* @return Generator */
        $generator = function () {
            foreach ($this->modules as $module) {
                yield from $module->getFactories();
            }
        };

        return iterator_to_array($generator());
    }

    /**
     * {@inheritdoc}
     *
     * @since [*next-version*]
     */
    public function getExtensions()
    {
        $extensions = [];

        foreach ($this->modules as $module) {
            foreach ($module->getExtensions() as $key => $extension) {
                if (array_key_exists($key, $extensions)) {
                    $current = $extensions[$key];
                    $extension = function (ContainerInterface $c, $previous) use ($current, $extension) {
                        $result1 = $current($c, $previous);
                        $result2 = $extension($c, $result1);
                        return $result2;
                    };
                }
                $extensions[$key] = $extension;
            }
        }

        return $extensions;
    }
}
