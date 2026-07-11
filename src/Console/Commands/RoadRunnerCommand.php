<?php

declare(strict_types=1);

namespace Scotch\Console\Commands;

use Scotch\Console\Command;

final class RoadRunnerCommand extends Command
{
    public function name(): string
    {
        return 'rr:install';
    }

    public function description(): string
    {
        return 'Download RoadRunner binary and scaffold .rr.yaml + worker.php';
    }

    public function execute(array $args): int
    {
        echo "\033[36mScaffolding RoadRunner configuration...\033[0m\n";

        $cwd = getcwd();
        $rrYamlPath = $cwd . '/.rr.yaml';
        $workerPath = $cwd . '/worker.php';

        if (!file_exists($workerPath)) {
            echo "Creating worker.php...\n";
            $workerContent = <<<'PHP'
<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Scotch\Application;

$app = require __DIR__ . '/bootstrap/app.php';
$app->runRoadRunner();

echo "RoadRunner worker started.\n";
PHP;
            file_put_contents($workerPath, $workerContent);
        }

        if (!file_exists($rrYamlPath)) {
            echo "Creating .rr.yaml...\n";
            $rrYamlContent = <<<'YAML'
version: "3"

rpc:
  listen: tcp://:6001

server:
  command: "php worker.php"

http:
  address: 0.0.0.0:8080
  middleware: ["static"]
  static:
    dir: "public"
    forbid: [".php", ".htaccess"]
  pool:
    num_workers: 0
    debug: true
YAML;
            file_put_contents($rrYamlPath, $rrYamlContent);
        }

        $binDir = $cwd . '/bin';
        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $rrCliPath = $cwd . '/vendor/bin/rr';
        if (!file_exists($rrCliPath)) {
            echo "\033[31mError: RoadRunner CLI utility not found in vendor/bin/rr.\033[0m\n";
            echo "Please run 'composer install' or 'composer update' first.\n";
            return 1;
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

        $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
        $destBin = $binDir . '/' . $binName;

        if (file_exists($destBin)) {
            chmod($destBin, 0755);
            echo "\033[32mSuccessfully installed RoadRunner binary to: {$destBin}\033[0m\n";
            echo "Run RoadRunner with: ./bin/rr serve\n";
            return 0;
        }

        echo "\033[31mInstallation failed: rr binary not found at {$destBin}\033[0m\n";
        return 1;
    }
}
