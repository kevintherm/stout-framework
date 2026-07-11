<?php

declare(strict_types=1);

namespace Scotch\Tests\Feature;

use Scotch\Application;
use Scotch\Config\Config;
use Scotch\Console\Kernel as ConsoleKernel;

test('application boots with configuration and kernel bindings', function () {
    $basePath = realpath(__DIR__ . '/../../');
    $app = new Application(
        basePath: is_string($basePath) ? $basePath : __DIR__ . '/../../'
    );

    $container = $app->getContainer();

    expect($container->has(Config::class))->toBeTrue()
        ->and($container->get(Config::class))->toBeInstanceOf(Config::class)
        ->and($container->has(ConsoleKernel::class))->toBeTrue()
        ->and($container->get(ConsoleKernel::class))->toBeInstanceOf(ConsoleKernel::class);
});
