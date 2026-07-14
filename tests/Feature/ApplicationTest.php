<?php

declare(strict_types=1);

namespace Stout\Tests\Feature;

use Stout\Application;
use Stout\Config\Config;
use Stout\Console\Kernel as ConsoleKernel;

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

test('running stout binary from outside the project root works', function () {
    $originalCwd = getcwd();
    $tempDir = sys_get_temp_dir() . '/stout_bin_test_' . uniqid();
    @mkdir($tempDir, 0755, true);

    chdir($tempDir);

    try {
        $stoutBin = realpath(__DIR__ . '/../../bin/stout');
        if ($stoutBin === false) {
            throw new \RuntimeException('stout binary not found');
        }
        $output = [];
        $resultCode = 0;

        exec("php " . escapeshellarg($stoutBin) . " list", $output, $resultCode);

        $outputStr = implode("\n", $output);
        expect($resultCode)->toBe(0);
        expect($outputStr)->toContain('Available commands');
        expect($outputStr)->not()->toContain('composer dependencies are not installed');
    } finally {
        @rmdir($tempDir);
        if (is_string($originalCwd)) {
            chdir($originalCwd);
        }
    }
});

