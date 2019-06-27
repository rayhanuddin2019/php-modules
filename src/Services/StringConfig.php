<?php

namespace Mecha\Modular\Services;

use Psr\Container\ContainerInterface;
use function array_map;
use function strval;

/**
 * A service helper for string values that need to be interpolated with other service values.
 *
 * @since [*next-version*]
 */
class StringConfig
{
    /**
     * @var string
     */
    public $string;

    /**
     * @var string[]
     */
    public $deps;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string   $string
     * @param string[] $deps
     */
    public function __construct($string, $deps)
    {
        $this->string = $string;
        $this->deps = $deps;
    }

    /**
     * @param ContainerInterface $c
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $c)
    {
        $args = array_map(
            function ($key) use ($c) {
                return ($key === 'c') ? $c : $c->get($key);
            },
            $this->deps
        );

        return $this->interpolate($this->string, $args);
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string   $message The string to interpolate.
     * @param string[] $context An associative array map of values to replace in the message.
     *
     * @return string The interpolated message.
     */
    protected function interpolate($message, array $context)
    {
        $replace = [];

        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = strval($val);
        }

        return strtr($message, $replace);
    }
}
