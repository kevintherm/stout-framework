<?php

declare(strict_types=1);

namespace Stout\Tests\Unit;

use Stout\Log\Logger;
use Psr\Log\LoggerInterface;

test('logger implements LoggerInterface', function () {
    $tempFile = sys_get_temp_dir() . '/stout_test_' . uniqid() . '.log';
    $logger = new Logger($tempFile);

    expect($logger)->toBeInstanceOf(LoggerInterface::class);

    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
});

test('logger writes messages to file with correct formatting', function () {
    $tempFile = sys_get_temp_dir() . '/stout_test_' . uniqid() . '.log';
    $logger = new Logger($tempFile);

    $logger->info('Hello, info level!', ['user' => 'Kevin']);
    $logger->error('Oops, an error occurred!');

    expect(file_exists($tempFile))->toBeTrue();

    $content = file_get_contents($tempFile);
    expect($content)->toContain('[INFO] Hello, info level! {"user":"Kevin"}')
        ->and($content)->toContain('[ERROR] Oops, an error occurred!');

    unlink($tempFile);
});

test('logger creates directory if it does not exist', function () {
    $tempDir = sys_get_temp_dir() . '/stout_nested_' . uniqid();
    $tempFile = $tempDir . '/app.log';
    $logger = new Logger($tempFile);

    expect(is_dir($tempDir))->toBeFalse();

    $logger->debug('Test nested creation');

    expect(is_dir($tempDir))->toBeTrue()
        ->and(file_exists($tempFile))->toBeTrue();

    unlink($tempFile);
    rmdir($tempDir);
});
