<?php

declare(strict_types=1);

namespace Stout\Console\Commands;

use Stout\Console\Command;
use Stout\Console\Kernel as ConsoleKernel;

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

    public function execute(array $args): int
    {
        $this->displayAscii();

        echo "Available commands:\n";
        echo "-------------------\n";

        /** @var ConsoleKernel $kernel */
        $kernel = $this->container->get(ConsoleKernel::class);
        $commands = $kernel->getCommands();

        $maxLength = 0;
        foreach ($commands as $cmd) {
            $maxLength = max($maxLength, strlen($cmd->name()));
        }

        foreach ($commands as $cmd) {
            $paddedName = str_pad($cmd->name(), $maxLength + 2, ' ');
            echo "  \033[32m{$paddedName}\033[0m {$cmd->description()}\n";
        }

        echo PHP_EOL;
        return 0;
    }

    private function displayAscii(): void
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
                    echo $content . PHP_EOL;
                    break;
                }
            }
        }
    }
}
