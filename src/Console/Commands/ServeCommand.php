<?php

declare(strict_types=1);

namespace Stout\Console\Commands;

use Stout\Config\Config;
use Stout\Console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    protected function configure(): void
    {
        $this
            ->addOption('host', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'The host to serve the application on.')
            ->addOption('port', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'The port to serve the application on.')
            ->addOption('php', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Use the PHP built-in development server.')
            ->addOption('driver', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'The driver to use (e.g. php).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->container->get(Config::class);
        /** @var Config $config */

        /** @var string|null $cliHost */
        $cliHost = $input->getOption('host');
        /** @var string|null $cliPort */
        $cliPort = $input->getOption('port');

        $hostVal = $config->get('app.host', '127.0.0.1');
        $portVal = $config->get('app.port', '8000');
        $host = $cliHost ?? (is_scalar($hostVal) ? (string) $hostVal : '127.0.0.1');
        $port = $cliPort ?? (is_scalar($portVal) ? (string) $portVal : '8000');

        $hasCliAddress = $cliHost !== null || $cliPort !== null;
        
        $this->displayAscii($output);

        $publicDir = getcwd() . '/public';
        if (!file_exists($publicDir)) {
            if (!mkdir($publicDir, 0755, true) && !is_dir($publicDir)) {
                $output->writeln("<error>Error: Failed to create public directory: {$publicDir}</error>");
                return 1;
            }
        }

        $driver = $input->getOption('driver');
        $usePhpServer = $input->getOption('php') === true || $driver === 'php';
        if ($usePhpServer) {
            $output->writeln("<info>Starting PHP built-in development server on http://{$host}:{$port}</info>");
            $output->writeln("Document root: {$publicDir}");
            $output->writeln("Press Ctrl+C to stop.\n");

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
            $output->writeln("<error>Error: Could not get current working directory.</error>");
            return 1;
        }

        $rrYaml = $cwd . '/rr.yaml';
        $binDir = $cwd . '/bin';
        $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
        $rrBin = $binDir . '/' . $binName;

        $needsYaml = !$this->checkFileExists($rrYaml);
        $needsBin = !$this->checkFileExists($rrBin);

        if ($needsYaml) {
            $output->writeln("Scaffolding RoadRunner configuration...");
            $output->writeln("Creating rr.yaml...");

            $entryPoint = 'app.php';
            $isTesting = class_exists(\PHPUnit\Framework\TestCase::class) || defined('PHPUNIT_COMPOSER_INSTALL');
            $noInteraction = !$input->isInteractive();

            if (PHP_SAPI === 'cli' && stream_isatty(STDIN) && !$isTesting && !$noInteraction) {
                $output->write("Where is the entry point file? [default: app.php]: ");
                $userInput = fgets(STDIN);
                if ($userInput !== false) {
                    $userInput = trim($userInput);
                    if ($userInput !== '') {
                        $entryPoint = ltrim($userInput, '/');
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
                $output->writeln("<error>Error writing rr.yaml: " . $e->getMessage() . "</error>");
                return 1;
            }
        }

        if ($needsBin) {
            $rrCliPath = $cwd . '/vendor/bin/rr';
            if (!$this->checkFileExists($rrCliPath)) {
                $output->writeln("<error>Error: RoadRunner CLI utility not found in vendor/bin/rr.</error>");
                $output->writeln("Please run 'composer install' or 'composer update' first.");
                return 1;
            }

            if (!is_dir($binDir)) {
                mkdir($binDir, 0755, true);
            }

            $output->writeln("Downloading RoadRunner binary via CLI utility...");

            $command = sprintf(
                'php %s get-binary -l %s --no-config --silent --no-interaction',
                escapeshellarg($rrCliPath),
                escapeshellarg($binDir . '/')
            );

            $resultCode = 0;
            $execOutput = [];
            exec($command, $execOutput, $resultCode);

            if ($resultCode !== 0) {
                $output->writeln("<error>Failed to install RoadRunner binary using CLI utility. Exit code: {$resultCode}</error>");
                return 1;
            }

            if ($this->checkFileExists($rrBin)) {
                chmod($rrBin, 0755);
                $output->writeln("<info>Successfully installed RoadRunner binary to: {$rrBin}</info>");
            } else {
                $output->writeln("<error>Installation failed: rr binary not found at {$rrBin}</error>");
                return 1;
            }
        } else {
            chmod($rrBin, 0755);
        }

        if (!$hasCliAddress && file_exists($rrYaml)) {
            try {
                $yamlData = \Symfony\Component\Yaml\Yaml::parseFile($rrYaml);
                if (is_array($yamlData) && isset($yamlData['http']) && is_array($yamlData['http']) && isset($yamlData['http']['address']) && is_string($yamlData['http']['address'])) {
                    $address = $yamlData['http']['address'];
                    $parts = explode(':', $address);
                    if (count($parts) === 2) {
                        $host = $parts[0];
                        $port = $parts[1];
                    }
                }
            } catch (\Throwable $e) {
                // Ignore parsing errors, fall back to defaults
            }
        }

        $output->writeln("<info>Starting RoadRunner development server on http://{$host}:{$port}</info>");
        $output->writeln("Press Ctrl+C to stop.\n");

        if ($hasCliAddress) {
            $command = sprintf(
                '%s serve -c %s -o http.address=%s',
                escapeshellarg($rrBin),
                escapeshellarg($rrYaml),
                escapeshellarg($host . ':' . $port)
            );
        } else {
            $command = sprintf(
                '%s serve -c %s',
                escapeshellarg($rrBin),
                escapeshellarg($rrYaml)
            );
        }

        passthru($command);
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

    /**
     * @phpstan-impure
     */
    private function checkFileExists(string $path): bool
    {
        return file_exists($path);
    }
}
