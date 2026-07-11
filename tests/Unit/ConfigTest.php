<?php

declare(strict_types=1);

namespace Stout\Tests\Unit;

use Stout\Config\Config;
use Stout\Exceptions\StoutException;

test('it can retrieve values with dot notation', function () {
    $config = new Config([
        'app' => [
            'name' => 'Stout',
            'debug' => true,
        ],
    ]);

    expect($config->get('app.name'))->toBe('Stout')
        ->and($config->get('app.debug'))->toBeTrue()
        ->and($config->get('app.nonexistent', 'default'))->toBe('default');
});

test('it throws exception on require when key is missing', function () {
    $config = new Config([
        'foo' => 'bar',
    ]);

    expect(fn() => $config->require('baz'))->toThrow(StoutException::class);
});

test('it merges config arrays correctly', function () {
    $config1 = new Config([
        'app' => [
            'name' => 'Stout',
            'debug' => false,
        ],
    ]);

    $config2 = new Config([
        'app' => [
            'debug' => true,
            'url' => 'http://localhost',
        ],
    ]);

    $merged = $config1->merge($config2);

    expect($merged->get('app.name'))->toBe('Stout')
        ->and($merged->get('app.debug'))->toBeTrue()
        ->and($merged->get('app.url'))->toBe('http://localhost');
});
