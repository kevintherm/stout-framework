<?php

declare(strict_types=1);

namespace Stout\Console;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class Command extends SymfonyCommand
{
    public function __construct(protected readonly ContainerInterface $container)
    {
        parent::__construct($this->name());
        $this->setDescription($this->description());
    }

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
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int Exit code (0 for success, non-zero for failure).
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::SUCCESS;
    }
}

