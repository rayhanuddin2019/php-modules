<?php

use Mecha\Modular\Containers\CachingContainer;
use Mecha\Modular\Containers\ServiceProviderContainer;
use Mecha\Modular\Example\modules\HelloModule;
use Mecha\Modular\Example\modules\JapanTimeModule;
use Mecha\Modular\Example\modules\MainModule;
use Mecha\Modular\ModularModule;
use Mecha\Modular\PrefixChangeModule;

require_once __DIR__ . '/../vendor/autoload.php';

$modules = [
    'hello' => new HelloModule(),
    'hello_time' => new PrefixChangeModule(new HelloModule(), 'hello/', 'hello_time/'),
    'japan_time' => new JapanTimeModule(),
    'main' => new MainModule(),
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
