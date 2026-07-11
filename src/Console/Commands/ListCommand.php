<?php

declare(strict_types=1);

namespace Scotch\Console\Commands;

use Scotch\Console\Command;
use Scotch\Console\Kernel as ConsoleKernel;

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
            getcwd() . '/vendor/scotch/scotch/ascii.txt',
        ];

        $composerJsonPath = __DIR__ . '/../../../composer.json';
        $version = 'version unknown';
        if (file_exists($composerJsonPath)) {
            $rawJson = file_get_contents($composerJsonPath);
            if (is_string($rawJson)) {
                $composerData = json_decode($rawJson, true);
                if (is_array($composerData) && isset($composerData['version']) && is_string($composerData['version'])) {
                    $version = "v{$composerData['version']}";
                }
            }
        }

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $content = file_get_contents($path);
                if (is_string($content)) {
                    $content = strtr($content, [
                        '[#version]' => $version,
                    ]);

                    echo $content . PHP_EOL;
                    break;
                }
            }
        }
    }
}
