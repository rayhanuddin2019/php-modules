<?php

namespace Mecha\Modular\Services;

use Mecha\Modular\Service;
use Psr\Container\ContainerInterface;

/**
 * A simple service helper class similar to {@link Factory} that returns the callback rather than invoking it.
 */
class Callback extends Service
{
    /**
     * @param ContainerInterface $c
     *
     * @return callable
     */
    public function __invoke(ContainerInterface $c)
    {
        return function () use ($c) {
            return parent::__invoke($c);
        };
    }
}
