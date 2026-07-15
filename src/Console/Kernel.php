<?php

declare(strict_types=1);

namespace Stout\Console;

use Psr\Container\ContainerInterface;
use Stout\Console\Command;
use Stout\Console\Commands\ListCommand;
use Stout\Console\Commands\ServeCommand;
use Stout\Exceptions\StoutException;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

final class Kernel
{
    private SymfonyApplication $cliApp;

    /**
     * @param ContainerInterface $container
     * @param array<class-string<Command>> $commandClasses
     */
    public function __construct(
        private readonly ContainerInterface $container,
        array $commandClasses = []
    ) {
        $version = $this->resolveVersion();
        $this->cliApp = new SymfonyApplication('Stout', $version);

        $builtIns = [
            ListCommand::class,
            ServeCommand::class,
        ];

        foreach (array_merge($builtIns, $commandClasses) as $class) {
            $this->register($class);
        }
    }

    /**
     * Register a Command class.
     *
     * @param class-string<Command> $class
     */
    public function register(string $class): void
    {
        if (!class_exists($class) || !is_subclass_of($class, Command::class)) {
            throw new StoutException("Invalid command class: {$class}. Must extend Stout\\Console\\Command.");
        }

        $command = new $class($this->container);
        $this->cliApp->addCommand($command);
    }

    /**
     * Get all registered commands.
     *
     * @return array<string, Command>
     */
    public function getCommands(): array
    {
        $commands = [];
        foreach ($this->cliApp->all() as $name => $command) {
            if ($command instanceof Command) {
                $commands[$name] = $command;
            }
        }
        /** @var array<string, Command> $commands */
        return $commands;
    }

    /**
     * Dispatch the CLI command.
     *
     * @param array<int, string> $argv
     * @return int Exit code
     */
    public function handle(array $argv): int
    {
        $input = new ArgvInput(array_values($argv));
        $output = new ConsoleOutput();

        try {
            $this->cliApp->setAutoExit(false);
            return $this->cliApp->run($input, $output);
        } catch (\Throwable $e) {
            $output->writeln("<error>Fatal Command Error: " . $e->getMessage() . "</error>");
            $output->writeln($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Resolve the application version from the git tag or Composer metadata.
     */
    private function resolveVersion(): string
    {
        $version = null;

        try {
            /** @var \Stout\Config\Config $config */
            $config = $this->container->get(\Stout\Config\Config::class);
            $basePath = $config->get('paths.base');
            if (is_string($basePath) && is_dir($basePath . '/.git') && function_exists('exec')) {
                $output = [];
                $resultCode = 0;
                @exec('git describe --tags --always', $output, $resultCode);
                if ($resultCode === 0 && isset($output[0])) {
                    $version = trim($output[0]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore config/exec resolution issues
        }

        if ($version === null && class_exists(\Composer\InstalledVersions::class)) {
            try {
                $version = \Composer\InstalledVersions::getPrettyVersion('stout/stout');
            } catch (\OutOfBoundsException $e) {
                try {
                    $root = \Composer\InstalledVersions::getRootPackage();
                    $version = $root['pretty_version'];
                } catch (\Throwable $t) {
                    // Ignore
                }
            }
        }

        return $version ?? '1.0.0';
    }
}

