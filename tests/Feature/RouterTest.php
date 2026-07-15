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

test('router handles redirect and permanentRedirect', function () {
    $basePath = realpath(__DIR__ . '/../../');
    $app = new Application(
        basePath: is_string($basePath) ? $basePath : __DIR__ . '/../../'
    );

    $app->make(\Stout\Config\Config::class)->loadGroup('app', ['debug' => true]);
    $app->http()->bootstrap();

    $router = new Router();
    $router->redirect('/old-path', '/new-path');
    $router->permanentRedirect('/permanent-old', '/permanent-new');
    $router->redirect('/user/{id}/profile', '/profile/{id}');

    $app->http()->routes($router);

    // Test 302 redirect
    $psr7Request1 = \Slim\Psr7\Factory\ServerRequestFactory::createFromGlobals()
        ->withMethod('GET')
        ->withUri(new \Slim\Psr7\Uri('', '', null, '/old-path'));
    $request1 = new Request($psr7Request1);
    $response1 = $app->http()->handle($request1);
    expect($response1->getStatusCode())->toBe(302);
    expect($response1->getHeaderLine('Location'))->toBe('/new-path');

    // Test 301 redirect
    $psr7Request2 = \Slim\Psr7\Factory\ServerRequestFactory::createFromGlobals()
        ->withMethod('GET')
        ->withUri(new \Slim\Psr7\Uri('', '', null, '/permanent-old'));
    $request2 = new Request($psr7Request2);
    $response2 = $app->http()->handle($request2);
    expect($response2->getStatusCode())->toBe(301);
    expect($response2->getHeaderLine('Location'))->toBe('/permanent-new');

    // Test parameterized redirect
    $psr7Request3 = \Slim\Psr7\Factory\ServerRequestFactory::createFromGlobals()
        ->withMethod('GET')
        ->withUri(new \Slim\Psr7\Uri('', '', null, '/user/42/profile'));
    $request3 = new Request($psr7Request3);
    $response3 = $app->http()->handle($request3);
    expect($response3->getStatusCode())->toBe(302);
    expect($response3->getHeaderLine('Location'))->toBe('/profile/42');
});

