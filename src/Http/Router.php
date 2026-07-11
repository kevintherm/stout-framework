<?php

declare(strict_types=1);

namespace Stout\Http;

use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Interfaces\RouteInterface;
use Slim\Interfaces\RouteGroupInterface;

final readonly class Router
{
    /**
     * @param App<ContainerInterface> $app
     */
    public function __construct(private App $app) {}

    /**
     * @param string $pattern
     * @param array{class-string, string}|callable|string $callable
     */
    public function get(string $pattern, array|callable|string $callable): RouteInterface
    {
        return $this->app->get($pattern, $callable);
    }

    /**
     * @param string $pattern
     * @param array{class-string, string}|callable|string $callable
     */
    public function post(string $pattern, array|callable|string $callable): RouteInterface
    {
        return $this->app->post($pattern, $callable);
    }

    /**
     * @param string $pattern
     * @param array{class-string, string}|callable|string $callable
     */
    public function put(string $pattern, array|callable|string $callable): RouteInterface
    {
        return $this->app->put($pattern, $callable);
    }

    /**
     * @param string $pattern
     * @param array{class-string, string}|callable|string $callable
     */
    public function patch(string $pattern, array|callable|string $callable): RouteInterface
    {
        return $this->app->patch($pattern, $callable);
    }

    /**
     * @param string $pattern
     * @param array{class-string, string}|callable|string $callable
     */
    public function delete(string $pattern, array|callable|string $callable): RouteInterface
    {
        return $this->app->delete($pattern, $callable);
    }

    /**
     * @param string $pattern
     * @param callable|string $callable
     */
    public function group(string $pattern, callable|string $callable): RouteGroupInterface
    {
        return $this->app->group($pattern, $callable);
    }
}
