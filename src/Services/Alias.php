<?php

namespace Mecha\Modular\Services;

use Psr\Container\ContainerInterface;
use function call_user_func_array;

/**
 * A service helper that creates an alias for another existing service.
 * Can optionally default to invoking a callback and return its value if the original service does not exist.
 */
class Alias
{
    /**
     * @var string
     */
    public $key;

    /**
     * @var callable
     */
    public $default;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string $key
     * @param callable     $default
     */
    public function __construct(string $key, callable $default = null)
    {
        $this->key = $key;
        $this->default = $default;
    }

    /**
     * @param ContainerInterface $c
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $c)
    {
        if ($this->default === null) {
            return $c->get($this->key);
        }

        if ($c->has($this->key)) {
            return $c->get($this->key);
        }

        return call_user_func_array($this->default, [$c]);
    }
}
