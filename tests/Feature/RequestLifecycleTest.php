<?php

declare(strict_types=1);

namespace Stout\Tests\Feature;

use Stout\Application;
use Stout\Http\RequestLifecycle;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

test('request lifecycle triggers before and after hooks in run', function () {
    $basePath = realpath(__DIR__ . '/../../');
    $app = new Application(
        basePath: is_string($basePath) ? $basePath : __DIR__ . '/../../'
    );

    $app->http()->bootstrap();
    $app->http()->routes(function ($router) {
        $router->get('/', function ($req, \Slim\Http\Response $res) {
            return $res->withJson(['status' => 'ok']);
        });
    });

    $lifecycle = $app->make(RequestLifecycle::class);

    $trace = [];
    $lifecycle->before(function (ServerRequestInterface $request) use (&$trace) {
        $trace[] = 'before';
        expect($request->getUri()->getPath())->toBe('/');
    });

    $lifecycle->after(function (?ResponseInterface $response, ?\Throwable $exception) use (&$trace) {
        $trace[] = 'after';
        expect($response)->toBeInstanceOf(ResponseInterface::class);
        expect($exception)->toBeNull();
    });

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';

    ob_start();
    try {
        $app->http()->runCgi();
    } finally {
        ob_end_clean();
    }

    expect($trace)->toBe(['before', 'after']);
});
