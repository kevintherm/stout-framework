<?php

declare(strict_types=1);

namespace Stout;

use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Stout\Config\Config;
use Stout\Console\Command;
use Stout\Console\Kernel as ConsoleKernel;
use Stout\Container\ContainerFactory;
use Stout\Http\Kernel as HttpKernel;
use Stout\Http\RequestLifecycle;
use Stout\Log\Logger;
use Stout\Support\ServiceProvider;

final class Application
{
    private static ?self $instance = null;
    private ContainerInterface $container;
    private ?HttpKernel $http = null;

    /**
     * @param string $basePath Project root directory
     * @param array<class-string<ServiceProvider>> $providers
     * @param array<class-string<Command>> $commands
     */
    public function __construct(
        private readonly string $basePath,
        array $providers = [],
        array $commands = []
    ) {
        self::$instance = $this;

        if (file_exists($this->basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($this->basePath);
            $dotenv->safeLoad();
        }

        $config = new Config([
            'app' => [
                'name' => $_ENV['APP_NAME'] ?? 'Stout',
                'env' => $_ENV['APP_ENV'] ?? 'production',
                'debug' => (bool) ($_ENV['APP_DEBUG'] ?? false),
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                'host' => $_ENV['APP_HOST'] ?? '127.0.0.1',
                'port' => $_ENV['APP_PORT'] ?? '8000',
                'log_path' => $_ENV['APP_LOG_PATH'] ?? $this->basePath . '/storage/logs/app.log',
            ],
            'paths' => [
                'base' => $this->basePath,
            ],
        ]);

        /** @var array<string, mixed> $definitions */
        $definitions = [
            ConsoleKernel::class => fn(ContainerInterface $c) => new ConsoleKernel($c, $commands),
            self::class => $this,
            ResponseFactoryInterface::class => function (ContainerInterface $c) {
                return new \Stout\Http\Factory\DecoratedResponseFactory(
                    new \Slim\Psr7\Factory\ResponseFactory(),
                    new \Slim\Psr7\Factory\StreamFactory()
                );
            },
            LoggerInterface::class => function (ContainerInterface $c) {
                /** @var Config $config */
                $config = $c->get(Config::class);

                $logPathVal = $config->get('app.log_path');
                $logPath = is_string($logPathVal) ? $logPathVal : $this->basePath . '/storage/logs/app.log';

                $timezoneVal = $config->get('app.timezone');
                $timezone = is_string($timezoneVal) ? $timezoneVal : null;

                return new Logger($logPath, $timezone);
            },
            RequestLifecycle::class => fn(ContainerInterface $c) => new RequestLifecycle(),
        ];

        $this->container = ContainerFactory::build($config, $providers, $definitions);
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application has not been initialized.');
        }

        return self::$instance;
    }

    /**
     * Get the DI Container.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Get the Config object for reading or merging additional groups.
     */
    public function config(): Config
    {
        /** @var Config $config */
        $config = $this->container->get(Config::class);
        return $config;
    }

    /**
     * Resolve a dependency directly from the container.
     *
     * @template T
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : mixed)
     */
    public function make(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Get the HTTP Kernel.
     */
    public function http(): HttpKernel
    {
        if ($this->http === null) {
            $this->http = new HttpKernel($this->container);
        }
        
        return $this->http;
    }

    /**
     * Run in FPM/CGI mode.
     */
    public function runCgi(): void
    {
        $this->http()->bootstrap()->runCgi();
    }

    /**
     * Run in RoadRunner HTTP worker daemon mode (default).
     */
    public function run(): void
    {
        $this->http()->bootstrap()->run();
    }

    /**
     * Run in CLI/Console mode.
     *
     * @param array<int, string> $argv
     * @return int Exit code
     */
    public function runCli(array $argv): int
    {
        /** @var ConsoleKernel $console */
        $console = $this->container->get(ConsoleKernel::class);
        return $console->handle($argv);
    }
}
