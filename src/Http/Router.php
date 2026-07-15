<?php

declare(strict_types=1);

namespace Stout\Http;

use Slim\Interfaces\RouteCollectorProxyInterface;

final class Router
{
    /** @var array<Route> */
    private array $routes = [];

    /** @var array<array{prefix: string, router: Router}> */
    private array $mounted = [];

    /** @var array<callable|\Psr\Http\Server\MiddlewareInterface|string> */
    private array $middleware = [];

    public function __construct() {}

    /**
     * Register a GET route.
     *
     * @param array{class-string, string}|callable|string $callable
     */
    public function get(string $pattern, array|callable|string $callable): Route
    {
        return $this->map(['GET'], $pattern, $callable);
    }

    /**
     * Register a POST route.
     *
     * @param array{class-string, string}|callable|string $callable
     */
    public function post(string $pattern, array|callable|string $callable): Route
    {
        return $this->map(['POST'], $pattern, $callable);
    }

    /**
     * Register a PUT route.
     *
     * @param array{class-string, string}|callable|string $callable
     */
    public function put(string $pattern, array|callable|string $callable): Route
    {
        return $this->map(['PUT'], $pattern, $callable);
    }

    /**
     * Register a PATCH route.
     *
     * @param array{class-string, string}|callable|string $callable
     */
    public function patch(string $pattern, array|callable|string $callable): Route
    {
        return $this->map(['PATCH'], $pattern, $callable);
    }

    /**
     * Register a DELETE route.
     *
     * @param array{class-string, string}|callable|string $callable
     */
    public function delete(string $pattern, array|callable|string $callable): Route
    {
        return $this->map(['DELETE'], $pattern, $callable);
    }

    /**
     * Register a route matching any standard HTTP method.
     *
     * @param array{class-string, string}|callable|string $callable
     */
    public function any(string $pattern, array|callable|string $callable): Route
    {
        return $this->map(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], $pattern, $callable);
    }

    /**
     * Register a route with custom HTTP methods.
     *
     * @param array<string> $methods
     * @param array{class-string, string}|callable|string $callable
     */
    public function map(array $methods, string $pattern, array|callable|string $callable): Route
    {
        $route = new Route($methods, $pattern, $callable);
        $this->routes[] = $route;
        return $route;
    }

    /**
     * Register a redirect route.
     */
    public function redirect(string $from, string $to, int $status = 302): Route
    {
        return $this->any($from, function (Request $request, Response $response, array $args) use ($to, $status) {
            $destination = $to;
            foreach ($args as $key => $value) {
                if (is_scalar($value)) {
                    $destination = str_replace('{' . $key . '}', (string) $value, $destination);
                }
            }
            return $response->redirect($destination, $status);
        });
    }

    /**
     * Register a permanent redirect route.
     */
    public function permanentRedirect(string $from, string $to): Route
    {
        return $this->redirect($from, $to, 301);
    }

    /**
     * Add middleware to all routes registered in this router and its sub-routers.
     *
     * @param callable|\Psr\Http\Server\MiddlewareInterface|string $middleware
     */
    public function middleware(callable|object|string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Mount a sub-router under a prefix.
     */
    public function mount(string $prefix, Router $router): self
    {
        $this->mounted[] = ['prefix' => $prefix, 'router' => $router];
        return $this;
    }

    /**
     * Merge another router directly into this router (without prefixing).
     */
    public function merge(Router $router): self
    {
        foreach ($router->routes as $route) {
            $this->routes[] = $route;
        }
        foreach ($router->mounted as $mount) {
            $this->mounted[] = $mount;
        }
        foreach ($router->middleware as $mw) {
            $this->middleware[] = $mw;
        }
        return $this;
    }

    /**
     * Register accumulated routes and mounted sub-routers to a Slim proxy/app.
     *
     * @param RouteCollectorProxyInterface<\Psr\Container\ContainerInterface> $proxy
     */
    public function registerToProxy(RouteCollectorProxyInterface $proxy): void
    {
        // 1. Register simple routes
        foreach ($this->routes as $route) {
            $r = $proxy->map($route->getMethods(), $route->getPattern(), $route->getCallable());
            
            // Route-level middleware
            foreach ($route->getMiddleware() as $mw) {
                $r->add($mw);
            }

            // Router-level middleware
            foreach ($this->middleware as $mw) {
                $r->add($mw);
            }

            if ($route->getName() !== null) {
                $r->setName($route->getName());
            }
        }

        // 2. Register mounted sub-routers
        foreach ($this->mounted as $mount) {
            $mountPrefix = '/' . ltrim($mount['prefix'], '/');
            
            $group = $proxy->group($mountPrefix, function (RouteCollectorProxyInterface $groupProxy) use ($mount) {
                $mount['router']->registerToProxy($groupProxy);
            });

            // Apply the parent router's middleware to the group
            foreach ($this->middleware as $mw) {
                $group->add($mw);
            }
        }
    }
}
