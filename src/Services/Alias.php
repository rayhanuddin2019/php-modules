<?php

namespace Mecha\Modular\Services;

use Psr\Container\ContainerInterface;
use function call_user_func_array;

/**
 * A service helper that creates an alias for another existing service.
 * Defaults to invoking a callback and returning its value if the original service does not exist.
 */
class Alias extends Service
{
    /**
     * @var string
     */
    public $key;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param string   $key
     * @param callable $default
     */
    public function __construct(string $key, callable $default)
    {
        parent::__construct([], $default);

        $this->key = $key;
    }

    /**
     * @param ContainerInterface $c
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $c)
    {
        if ($c->has($this->key)) {
            return $c->get($this->key);
        }

        return call_user_func_array($this->factory, [$c]);
    }
}
