<?php

namespace Mecha\Modular\Services;

use Psr\Container\ContainerInterface;

class Config
{
    /**
     * @var mixed
     */
    public $data;

    /**
     * Constructor.
     *
     * @since [*next-version*]
     *
     * @param mixed $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param ContainerInterface $c
     *
     * @return mixed
     */
    public function __invoke(ContainerInterface $c)
    {
        return $this->data;
    }
}
