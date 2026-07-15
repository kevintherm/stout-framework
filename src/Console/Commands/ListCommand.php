<?php

declare(strict_types=1);

namespace Stout\Console\Commands;

use Stout\Console\Command;
use Stout\Console\Kernel as ConsoleKernel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListCommand extends Command
{
    public function name(): string
    {
        return 'list';
    }

    public function description(): string
    {
        return 'List all available commands';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->displayAscii($output);

        $output->writeln("Available commands:");
        $output->writeln("-------------------");

        /** @var ConsoleKernel $kernel */
        $kernel = $this->container->get(ConsoleKernel::class);
        $commands = $kernel->getCommands();

        $maxLength = 0;
        foreach ($commands as $cmd) {
            $maxLength = max($maxLength, strlen($cmd->name()));
        }

        foreach ($commands as $cmd) {
            $paddedName = str_pad($cmd->name(), $maxLength + 2, ' ');
            $output->writeln("  <info>{$paddedName}</info> {$cmd->description()}");
        }

        $output->writeln('');
        return 0;
    }

    private function displayAscii(OutputInterface $output): void
    {
        $possiblePaths = [
            __DIR__ . '/../../../ascii.txt',
            getcwd() . '/ascii.txt',
            getcwd() . '/vendor/stout/stout/ascii.txt',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                if (is_string($content)) {
                    $output->writeln($content);
                    break;
                }
            }
        }
    }
}

