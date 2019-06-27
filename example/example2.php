<?php

use Mecha\Modular\Containers\CachingContainer;
use Mecha\Modular\Containers\ServiceProviderContainer;
use Mecha\Modular\Example\modules\GreetingModule;
use Mecha\Modular\Example\modules\HelloModule;
use Mecha\Modular\Example\modules\JapanTimeModule;
use Mecha\Modular\Example\modules\MainModule;
use Mecha\Modular\ModularModule;
use Mecha\Modular\ModuleInterface;
use Mecha\Modular\MultiBoxModule2;
use Mecha\Modular\PrefixedModule;
use Mecha\Modular\Services\Config;
use Psr\Container\ContainerInterface;

require_once __DIR__ . '/../vendor/autoload.php';

$modules = [
    'admin_greet' => new PrefixedModule(new GreetingModule(), 'admin_greet/'),
    'comp_greet' => new PrefixedModule(new GreetingModule(), 'comp_greet/'),
    'main' => new class implements ModuleInterface {
        public function run(ContainerInterface $c)
        {
        }

        public function getFactories()
        {
            return [];
        }

        public function getExtensions()
        {
            return [
                'admin_greet/name' => new Config('Administrator'),
                'comp_greet/name' => new Config('Computer'),
            ];
        }
    },
];

// Create the app as a module that consists of other modules
$app = new ModularModule($modules);

// $c will be the top-level container used by the app and its modules
$c = null;
// This function provides $c, and will be given to the inner container for delegation
$parentCb = function () use (&$c) {
    return $c;
};

// The inner DI container for the app
$spContainer = new ServiceProviderContainer($app, $parentCb);

// The top-level DI container
$c = new CachingContainer($spContainer);

// Run the app (which in turn runs its modules)
$app->run($c);
