<?php

declare(strict_types=1);

namespace Scotch\Tests\Unit;

use Scotch\Config\Config;
use Scotch\Exceptions\ScotchException;

test('it can retrieve values with dot notation', function () {
    $config = new Config([
        'app' => [
            'name' => 'Scotch',
            'debug' => true,
        ],
    ]);

    expect($config->get('app.name'))->toBe('Scotch')
        ->and($config->get('app.debug'))->toBeTrue()
        ->and($config->get('app.nonexistent', 'default'))->toBe('default');
});

test('it throws exception on require when key is missing', function () {
    $config = new Config([
        'foo' => 'bar',
    ]);

    expect(fn() => $config->require('baz'))->toThrow(ScotchException::class);
});

test('it merges config arrays correctly', function () {
    $config1 = new Config([
        'app' => [
            'name' => 'Scotch',
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

    expect($merged->get('app.name'))->toBe('Scotch')
        ->and($merged->get('app.debug'))->toBeTrue()
        ->and($merged->get('app.url'))->toBe('http://localhost');
});
