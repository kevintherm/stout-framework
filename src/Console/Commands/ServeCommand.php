<?php

declare(strict_types=1);

namespace Stout\Console\Commands;

use Stout\Config\Config;
use Stout\Console\Command;
use Stout\Console\Commands\RoadRunnerCommand;

final class ServeCommand extends Command
{
    public function name(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Start the development server (RoadRunner by default, PHP built-in server with --php)';
    }

    public function execute(array $args): int
    {
        $config = $this->container->get(Config::class);
        /** @var Config $config */

        $hostVal = $config->get('app.host', '127.0.0.1');
        $portVal = $config->get('app.port', '8000');
        $host = is_scalar($hostVal) ? (string) $hostVal : '127.0.0.1';
        $port = is_scalar($portVal) ? (string) $portVal : '8000';
        
        $publicDir = getcwd() . '/public';

        if (!file_exists($publicDir)) {
            echo "\033[31mError:\033[0m Public directory not found: {$publicDir}\n";
            return 1;
        }

        $this->displayAscii();

        $usePhpServer = in_array('--php', $args, true) || in_array('--driver=php', $args, true);

        if ($usePhpServer) {
            echo "\033[32mStarting PHP built-in development server on http://{$host}:{$port}\033[0m\n";
            echo "Document root: {$publicDir}\n";
            echo "Press Ctrl+C to stop.\n\n";

            $command = sprintf(
                'php -S %s:%s -t %s',
                escapeshellarg($host),
                escapeshellarg($port),
                escapeshellarg($publicDir)
            );

            passthru($command);
            return 0;
        }

        $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
        $rrBin = getcwd() . '/bin/' . $binName;

        if (!file_exists($rrBin)) {
            echo "RoadRunner binary not found. Triggering auto-installation...\n";
            $installer = new RoadRunnerCommand($this->container);
            $installExit = $installer->execute([]);
            if ($installExit !== 0) {
                echo "\033[31mFailed to install RoadRunner binary automatically. Aborting serve.\033[0m\n";
                return $installExit;
            }
        }

        $rrYaml = getcwd() . '/.rr.yaml';
        if (!file_exists($rrYaml)) {
            echo "RoadRunner configuration (.rr.yaml) not found. Generating default...\n";
            $installer = new RoadRunnerCommand($this->container);
            $installer->execute([]);
        }

        echo "\033[32mStarting RoadRunner development server on http://{$host}:{$port}\033[0m\n";
        echo "Press Ctrl+C to stop.\n\n";

        $command = sprintf(
            '%s serve -c %s -o http.address=%s:%s',
            escapeshellarg($rrBin),
            escapeshellarg($rrYaml),
            escapeshellarg($host . ':' . $port),
            escapeshellarg($host . ':' . $port)
        );

        $command = sprintf(
            '%s serve -c %s -o http.address=%s',
            escapeshellarg($rrBin),
            escapeshellarg($rrYaml),
            escapeshellarg($host . ':' . $port)
        );

        passthru($command);
        return 0;
    }
    
    private function displayAscii(): void
    {
        $possiblePaths = [
            __DIR__ . '/../../../ascii.txt',
            getcwd() . '/ascii.txt',
            getcwd() . '/vendor/stout/stout/ascii.txt',
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
