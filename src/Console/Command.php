<?php

declare(strict_types=1);

namespace Stout\Console;

use Psr\Container\ContainerInterface;

abstract class Command
{
    public function __construct(protected readonly ContainerInterface $container) {}

    /**
     * Get the name of the command (e.g. 'serve', 'rr:install').
     */
    abstract public function name(): string;

    /**
     * Get a description of what the command does.
     */
    abstract public function description(): string;

    /**
     * Execute the command.
     *
     * @param array<int, string> $args Positional arguments passed to the CLI command (excluding command name).
     * @return int Exit code (0 for success, non-zero for failure).
     */
    abstract public function execute(array $args): int;
}
