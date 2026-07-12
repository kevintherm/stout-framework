<?php

declare(strict_types=1);

namespace Stout\Console\Commands;

use Stout\Config\Config;
use Stout\Console\Command;

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
        
        $this->displayAscii();

        $publicDir = getcwd() . '/public';
        if (!file_exists($publicDir)) {
            if (!mkdir($publicDir, 0755, true) && !is_dir($publicDir)) {
                echo "\033[31mError:\033[0m Failed to create public directory: {$publicDir}\n";
                return 1;
            }
        }

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

        $cwd = getcwd();
        if ($cwd === false) {
            echo "\033[31mError: Could not get current working directory.\033[0m\n";
            return 1;
        }

        $rrYaml = $cwd . '/rr.yaml';
        $binDir = $cwd . '/bin';
        $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
        $rrBin = $binDir . '/' . $binName;

        $needsYaml = !$this->checkFileExists($rrYaml);
        $needsBin = !$this->checkFileExists($rrBin);

        if ($needsYaml) {
            echo "Scaffolding RoadRunner configuration...\n";
            echo "Creating rr.yaml...\n";

            $entryPoint = 'app.php';
            if (PHP_SAPI === 'cli' && stream_isatty(STDIN)) {
                echo "Where is the entry point file? [default: app.php]: ";
                $input = fgets(STDIN);
                if ($input !== false) {
                    $input = trim($input);
                    if ($input !== '') {
                        $entryPoint = ltrim($input, '/');
                    }
                }
            }

            try {
                $configYaml = [
                    'version' => '3',
                    'rpc' => [
                        'listen' => 'tcp://127.0.0.1:6001',
                    ],
                    'server' => [
                        'command' => 'php ' . $entryPoint,
                        'relay' => 'pipes',
                    ],
                    'http' => [
                        'address' => '0.0.0.0:8000',
                        'middleware' => [
                            'gzip',
                            'static',
                        ],
                        'static' => [
                            'dir' => 'public',
                            'forbid' => [
                                '.php',
                                '.htaccess',
                            ],
                        ],
                        'pool' => [
                            'num_workers' => 0,
                            'supervisor' => [
                                'max_worker_memory' => 100,
                            ],
                            'debug' => true,
                        ],
                    ],
                ];

                $yamlContent = \Symfony\Component\Yaml\Yaml::dump($configYaml, 4);
                file_put_contents($rrYaml, $yamlContent);
            } catch (\Throwable $e) {
                echo "\033[31mError writing rr.yaml: " . $e->getMessage() . "\033[0m\n";
                return 1;
            }
        }

        if ($needsBin) {
            $rrCliPath = $cwd . '/vendor/bin/rr';
            if (!$this->checkFileExists($rrCliPath)) {
                echo "\033[31mError: RoadRunner CLI utility not found in vendor/bin/rr.\033[0m\n";
                echo "Please run 'composer install' or 'composer update' first.\n";
                return 1;
            }

            if (!is_dir($binDir)) {
                mkdir($binDir, 0755, true);
            }

            echo "Downloading RoadRunner binary via CLI utility...\n";

            $command = sprintf(
                'php %s get-binary -l %s --no-config --silent --no-interaction',
                escapeshellarg($rrCliPath),
                escapeshellarg($binDir . '/')
            );

            $resultCode = 0;
            $output = [];
            exec($command, $output, $resultCode);

            if ($resultCode !== 0) {
                echo "\033[31mFailed to install RoadRunner binary using CLI utility. Exit code: {$resultCode}\033[0m\n";
                return 1;
            }

            if ($this->checkFileExists($rrBin)) {
                chmod($rrBin, 0755);
                echo "\033[32mSuccessfully installed RoadRunner binary to: {$rrBin}\033[0m\n";
            } else {
                echo "\033[31mInstallation failed: rr binary not found at {$rrBin}\033[0m\n";
                return 1;
            }
        } else {
            chmod($rrBin, 0755);
        }

        echo "\033[32mStarting RoadRunner development server on http://{$host}:{$port}\033[0m\n";
        echo "Press Ctrl+C to stop.\n\n";

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

    /**
     * @phpstan-impure
     */
    private function checkFileExists(string $path): bool
    {
        return file_exists($path);
    }
}
