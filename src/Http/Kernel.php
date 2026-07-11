<?php

declare(strict_types=1);

namespace Stout\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Stout\Config\Config;
use Stout\Http\Middleware\ErrorMiddleware;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Http\Factory\DecoratedResponseFactory;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UploadedFileFactory;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;

final class Kernel
{
    /** @var App<ContainerInterface> */
    private App $app;

    public function __construct(private readonly ContainerInterface $container)
    {
        AppFactory::setResponseFactory(
            new DecoratedResponseFactory(new ResponseFactory(), new StreamFactory())
        );
        $this->app = AppFactory::createFromContainer($this->container);
    }

    /**
     * Set up routes via a user-defined routing callback.
     *
     * @param callable(Router): void $callback
     */
    public function routes(callable $callback): self
    {
        $router = new Router($this->app);
        $callback($router);
        return $this;
    }

    /**
     * Add middleware to the global HTTP stack.
     */
    public function middleware(MiddlewareInterface|string|callable $middleware): self
    {
        $this->app->add($middleware);
        return $this;
    }

    /**
     * Boots default/framework standard middleware (e.g. error handling).
     */
    public function bootstrap(): self
    {
        $config = $this->container->get(Config::class);
        /** @var Config $config */

        $debug = (bool) $config->get('app.debug', false);

        $logger = $this->container->get(\Psr\Log\LoggerInterface::class);
        /** @var \Psr\Log\LoggerInterface $logger */

        $this->app->add(new ErrorMiddleware(
            responseFactory: new DecoratedResponseFactory(new ResponseFactory(), new StreamFactory()),
            logger: $logger,
            displayErrorDetails: $debug,
            logErrors: true
        ));

        $this->app->addRoutingMiddleware();

        return $this;
    }

    /**
     * Run in traditional FPM/CGI mode.
     */
    public function run(): void
    {
        $this->app->run();
    }

    /**
     * Handle a single PSR-7 request (useful for testing or sub-dispatching).
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->handle($request);
    }

    /**
     * Run in RoadRunner daemon mode.
     */
    public function runRoadRunner(): void
    {
        $worker = Worker::create();
        
        $serverRequestFactory = new ServerRequestFactory();
        $streamFactory = new StreamFactory();
        $uploadsFactory = new UploadedFileFactory();

        $psr7Worker = new PSR7Worker(
            $worker,
            $serverRequestFactory,
            $streamFactory,
            $uploadsFactory
        );

        while ($request = $psr7Worker->waitRequest()) {
            try {
                $response = $this->app->handle($request);
                $psr7Worker->respond($response);
            } catch (\Throwable $e) {
                $worker->error((string) $e);
            }
        }
    }
}
