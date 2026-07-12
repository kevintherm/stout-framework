<?php

declare(strict_types=1);

namespace Stout\Tests\Feature;

use Stout\Application;
use Stout\Http\Router;
use Stout\Http\Request;
use Stout\Http\Response;

test('independent routers can be created, mounted, merged and registered', function () {
    $basePath = realpath(__DIR__ . '/../../');
    $app = new Application(
        basePath: is_string($basePath) ? $basePath : __DIR__ . '/../../'
    );

    $app->make(\Stout\Config\Config::class)->loadGroup('app', ['debug' => true]);
    $app->http()->bootstrap();

    // 1. Create independent API router
    $apiRouter = new Router();
    $apiRouter->get('/users', function (Request $request, Response $response) {
        $search = $request->query('search', 'none');
        return $response->json(['users' => ['Alice', 'Bob'], 'search' => $search]);
    })->setName('users.index');

    // 2. Create another router to merge
    $otherRouter = new Router();
    $otherRouter->get('/health', function (Request $request, Response $response) {
        return $response->json(['status' => 'healthy']);
    });

    // 3. Parent router
    $parentRouter = new Router();
    $parentRouter->mount('/api', $apiRouter);
    $parentRouter->merge($otherRouter);

    // Register on the application kernel
    $app->http()->routes($parentRouter);

    // Mock request for /api/users?search=test
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/users';
    $_SERVER['QUERY_STRING'] = 'search=test';

    ob_start();
    try {
        $app->http()->runCgi();
    } finally {
        $output = ob_get_clean();
    }
    expect($output)->toBeString();
    /** @var string $output */
    $data = json_decode($output, true);
    expect($data)->toBe([
        'users' => ['Alice', 'Bob'],
        'search' => 'test'
    ]);
});
