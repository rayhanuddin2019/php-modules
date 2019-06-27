<?php

namespace Mecha\Modular\Services;

use Psr\Container\ContainerInterface;
use function array_unshift;
use function call_user_func_array;

class Extension extends Factory
{
    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     *
     * @param mixed|null $prev
     */
    public function __invoke(ContainerInterface $c, $prev = null)
    {
        return call_user_func_array($this->callback, $this->getArgs($c, $prev));
    }

    /**
     * @inheritdoc
     *
     * @since [*next-version*]
     *
     * @param mixed|null $prev
     */
    protected function getArgs(ContainerInterface $c, $prev = null)
    {
        $args = parent::getArgs($c);

        array_unshift($args, $prev);

        return $args;
    }
}
