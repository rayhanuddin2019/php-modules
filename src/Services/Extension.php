<?php

namespace Mecha\Modular\Services;

use Mecha\Modular\Service;
use Psr\Container\ContainerInterface;
use function array_merge;
use function call_user_func_array;

/**
 * A service helper for service provider extensions. Similar to {@link Factory} but also passes the previous value.
 *
 * @see Service
 */
class Extension extends Service
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
        return call_user_func_array($this->factory, array_merge([$prev], $this->getDeps($c)));
    }
}
