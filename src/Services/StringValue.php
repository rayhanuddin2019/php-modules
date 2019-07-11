<?php

namespace Mecha\Modular\Services;

use Psr\Container\ContainerInterface;
use function strval;

/**
 * A service helper for string values that need to be interpolated with other service values.
 *
 * @since [*next-version*]
 */
class StringValue extends Service
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
    public function __construct($string, $deps = [])
    {
        $this->string = $string;

        parent::__construct($deps, [$this, 'interpolate']);
    }

    /**
     * Wrap deps in an array, to bypass them being unpacked into individual arguments.
     *
     * @param ContainerInterface $c
     *
     * @return array
     */
    protected function getDeps(ContainerInterface $c)
    {
        return [parent::getDeps($c)];
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string[] $context An associative array map of values to replace in the message.
     *
     * @return string The interpolated message.
     */
    protected function interpolate(array $context)
    {
        $replace = [];

        foreach ($context as $key => $val) {
            $replace['{' . $key . '}'] = strval($val);
        }

        return strtr($this->string, $replace);
    }
}
