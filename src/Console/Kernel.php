<?php

declare(strict_types=1);

namespace Stout\Console;

use Psr\Container\ContainerInterface;
use Stout\Console\Command;
use Stout\Console\Commands\ListCommand;
use Stout\Console\Commands\RoadRunnerCommand;
use Stout\Console\Commands\ServeCommand;
use Stout\Exceptions\StoutException;

final class Kernel
{
    /** @var array<string, Command> */
    private array $commands = [];

    /**
     * @param ContainerInterface $container
     * @param array<class-string<Command>> $commandClasses
     */
    public function __construct(
        private readonly ContainerInterface $container,
        array $commandClasses = []
    ) {
        $builtIns = [
            ListCommand::class,
            ServeCommand::class,
            RoadRunnerCommand::class,
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
        $this->commands[$command->name()] = $command;
    }

    /**
     * Get all registered commands.
     *
     * @return array<string, Command>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * Dispatch the CLI command.
     *
     * @param array<int, string> $argv
     * @return int Exit code
     */
    public function handle(array $argv): int
    {
        $commandName = $argv[1] ?? 'list';
        $args = array_slice($argv, 2);

        if (!isset($this->commands[$commandName])) {
            echo "\033[31mUnknown command: \"{$commandName}\"\033[0m\n\n";
            $commandName = 'list';
            $args = [];
        }

        try {
            return $this->commands[$commandName]->execute($args);
        } catch (\Throwable $e) {
            echo "\033[31mFatal Command Error: " . $e->getMessage() . "\033[0m\n";
            echo $e->getTraceAsString() . PHP_EOL;
            return 1;
        }
    }
}
