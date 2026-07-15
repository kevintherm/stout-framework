<?php

declare(strict_types=1);

namespace Stout\Console\Commands {
    /**
     * Shadow the global passthru function to prevent executing actual blocking processes in tests.
     */
    function passthru(string $command): void
    {
        $GLOBALS['last_executed_command'] = $command;
    }
}

namespace Stout\Tests\Feature {
    use Stout\Console\Commands\ServeCommand;
    use Stout\Tests\bootTestApp;

    test('serve command checks roadrunner setup and calls passthru with serve command', function () {
        $GLOBALS['last_executed_command'] = null;

        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/stout_serve_test_' . uniqid();
        @mkdir($tempDir, 0755, true);

        // Create dummy vendor/bin/rr
        @mkdir($tempDir . '/vendor/bin', 0755, true);
        $dummyRr = $tempDir . '/vendor/bin/rr';
        
        $dummyRrContent = <<<'PHP'
<?php
$args = $_SERVER['argv'];
if (in_array('get-binary', $args, true)) {
    $dirIndex = array_search('-l', $args, true);
    $dir = $dirIndex !== false ? $args[$dirIndex + 1] : getcwd() . '/bin/';
    @mkdir($dir, 0755, true);
    $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
    file_put_contents($dir . $binName, "dummy binary");
}
PHP;
        file_put_contents($dummyRr, $dummyRrContent);
        chmod($dummyRr, 0755);

        chdir($tempDir);

        try {
            $app = \Stout\Tests\bootTestApp();
            $command = new ServeCommand($app->getContainer());

            ob_start();
            $exitCode = $command->execute([]);
            $outputContent = ob_get_clean();

            expect($exitCode)->toBe(0);
            expect($outputContent)->toContain('Starting RoadRunner development server');
            
            // Check that passthru was called with the correct command
            $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
            expect($GLOBALS['last_executed_command'])->toContain("bin/{$binName}");
            expect($GLOBALS['last_executed_command'])->toContain("rr.yaml");
        } finally {
            // Clean up
            @unlink($tempDir . '/bin/' . (PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr'));
            @rmdir($tempDir . '/bin');
            @unlink($tempDir . '/rr.yaml');
            @unlink($dummyRr);
            @rmdir($tempDir . '/vendor/bin');
            @rmdir($tempDir . '/vendor');
            @rmdir($tempDir . '/public');
            @rmdir($tempDir);
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            $GLOBALS['last_executed_command'] = null;
        }
    });

    test('serve command does not scaffold or download when files already exist', function () {
        $GLOBALS['last_executed_command'] = null;

        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/stout_serve_exist_test_' . uniqid();
        @mkdir($tempDir, 0755, true);

        // Pre-create rr.yaml
        file_put_contents($tempDir . '/rr.yaml', "server:\n  command: \"php app.php\"\n");

        // Pre-create bin/rr
        @mkdir($tempDir . '/bin', 0755, true);
        $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
        file_put_contents($tempDir . '/bin/' . $binName, "dummy binary");

        chdir($tempDir);

        try {
            $app = \Stout\Tests\bootTestApp();
            $command = new ServeCommand($app->getContainer());

            ob_start();
            $exitCode = $command->execute([]);
            $outputContent = ob_get_clean();

            expect($exitCode)->toBe(0);
            expect($outputContent)->toContain('Starting RoadRunner development server');
            expect($outputContent)->not()->toContain('Scaffolding RoadRunner configuration');
            expect($outputContent)->not()->toContain('Downloading RoadRunner binary');
        } finally {
            @unlink($tempDir . '/bin/' . (PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr'));
            @rmdir($tempDir . '/bin');
            @unlink($tempDir . '/rr.yaml');
            @rmdir($tempDir . '/public');
            @rmdir($tempDir);
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            $GLOBALS['last_executed_command'] = null;
        }
    });

    test('serve command scaffolds config but does not download binary when binary exists but config is missing', function () {
        $GLOBALS['last_executed_command'] = null;

        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/stout_serve_partial_test_' . uniqid();
        @mkdir($tempDir, 0755, true);

        // Pre-create bin/rr
        @mkdir($tempDir . '/bin', 0755, true);
        $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
        file_put_contents($tempDir . '/bin/' . $binName, "dummy binary");

        chdir($tempDir);

        try {
            $app = \Stout\Tests\bootTestApp();
            $command = new ServeCommand($app->getContainer());

            ob_start();
            $exitCode = $command->execute([]);
            $outputContent = ob_get_clean();

            expect($exitCode)->toBe(0);
            expect($outputContent)->toContain('Starting RoadRunner development server');
            expect($outputContent)->toContain('Scaffolding RoadRunner configuration');
            expect($outputContent)->not()->toContain('Downloading RoadRunner binary');
            expect(file_exists($tempDir . '/rr.yaml'))->toBeTrue();
        } finally {
            @unlink($tempDir . '/bin/' . (PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr'));
            @rmdir($tempDir . '/bin');
            @unlink($tempDir . '/rr.yaml');
            @rmdir($tempDir . '/public');
            @rmdir($tempDir);
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            $GLOBALS['last_executed_command'] = null;
        }
    });

    test('serve command downloads binary but does not scaffold config when config exists but binary is missing', function () {
        $GLOBALS['last_executed_command'] = null;

        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/stout_serve_partial_bin_test_' . uniqid();
        @mkdir($tempDir, 0755, true);

        // Pre-create rr.yaml
        file_put_contents($tempDir . '/rr.yaml', "server:\n  command: \"php app.php\"\n");

        // Create dummy vendor/bin/rr for downloading binary
        @mkdir($tempDir . '/vendor/bin', 0755, true);
        $dummyRr = $tempDir . '/vendor/bin/rr';
        $dummyRrContent = <<<'PHP'
<?php
$args = $_SERVER['argv'];
if (in_array('get-binary', $args, true)) {
    $dirIndex = array_search('-l', $args, true);
    $dir = $dirIndex !== false ? $args[$dirIndex + 1] : getcwd() . '/bin/';
    @mkdir($dir, 0755, true);
    $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
    file_put_contents($dir . $binName, "dummy binary");
}
PHP;
        file_put_contents($dummyRr, $dummyRrContent);
        chmod($dummyRr, 0755);

        chdir($tempDir);

        try {
            $app = \Stout\Tests\bootTestApp();
            $command = new ServeCommand($app->getContainer());

            ob_start();
            $exitCode = $command->execute([]);
            $outputContent = ob_get_clean();

            expect($exitCode)->toBe(0);
            expect($outputContent)->toContain('Starting RoadRunner development server');
            expect($outputContent)->not()->toContain('Scaffolding RoadRunner configuration');
            expect($outputContent)->toContain('Downloading RoadRunner binary');
            
            $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
            expect(file_exists($tempDir . '/bin/' . $binName))->toBeTrue();
        } finally {
            @unlink($tempDir . '/bin/' . (PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr'));
            @rmdir($tempDir . '/bin');
            @unlink($tempDir . '/rr.yaml');
            @unlink($dummyRr);
            @rmdir($tempDir . '/vendor/bin');
            @rmdir($tempDir . '/vendor');
            @rmdir($tempDir . '/public');
            @rmdir($tempDir);
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            $GLOBALS['last_executed_command'] = null;
        }
    });

    test('serve command with php built-in server driver calls passthru with php -S', function () {
        $GLOBALS['last_executed_command'] = null;

        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/stout_serve_test_' . uniqid();
        @mkdir($tempDir, 0755, true);

        chdir($tempDir);

        try {
            $app = \Stout\Tests\bootTestApp();
            $command = new ServeCommand($app->getContainer());

            ob_start();
            $exitCode = $command->execute(['--php']);
            $outputContent = ob_get_clean();

            expect($exitCode)->toBe(0);
            expect($outputContent)->toContain('Starting PHP built-in development server');
            
            // Check that passthru was called with php -S
            expect($GLOBALS['last_executed_command'])->toContain("php -S");
        } finally {
            @rmdir($tempDir . '/public');
            @rmdir($tempDir);
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            $GLOBALS['last_executed_command'] = null;
        }
    });

    test('serve command does not override host and port if CLI arguments are not provided', function () {
        $GLOBALS['last_executed_command'] = null;

        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/stout_serve_no_cli_test_' . uniqid();
        @mkdir($tempDir, 0755, true);

        // Pre-create rr.yaml
        file_put_contents($tempDir . '/rr.yaml', "http:\n  address: \"0.0.0.0:8001\"\n");

        // Pre-create bin/rr
        @mkdir($tempDir . '/bin', 0755, true);
        $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
        file_put_contents($tempDir . '/bin/' . $binName, "dummy binary");

        chdir($tempDir);

        try {
            $app = \Stout\Tests\bootTestApp();
            $command = new ServeCommand($app->getContainer());

            ob_start();
            $exitCode = $command->execute([]);
            $outputContent = ob_get_clean();

            expect($exitCode)->toBe(0);
            expect($outputContent)->toContain('Starting RoadRunner development server on http://0.0.0.0:8001');
            
            // Check that passthru command does not contain -o http.address=
            expect($GLOBALS['last_executed_command'])->not()->toContain('-o http.address=');
        } finally {
            @unlink($tempDir . '/bin/' . (PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr'));
            @rmdir($tempDir . '/bin');
            @unlink($tempDir . '/rr.yaml');
            @rmdir($tempDir . '/public');
            @rmdir($tempDir);
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            $GLOBALS['last_executed_command'] = null;
        }
    });

    test('serve command overrides host and port if CLI arguments are provided', function () {
        $GLOBALS['last_executed_command'] = null;

        $originalCwd = getcwd();
        $tempDir = sys_get_temp_dir() . '/stout_serve_with_cli_test_' . uniqid();
        @mkdir($tempDir, 0755, true);

        // Pre-create rr.yaml
        file_put_contents($tempDir . '/rr.yaml', "http:\n  address: \"0.0.0.0:8001\"\n");

        // Pre-create bin/rr
        @mkdir($tempDir . '/bin', 0755, true);
        $binName = PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr';
        file_put_contents($tempDir . '/bin/' . $binName, "dummy binary");

        chdir($tempDir);

        try {
            $app = \Stout\Tests\bootTestApp();
            $command = new ServeCommand($app->getContainer());

            ob_start();
            $exitCode = $command->execute(['--host=127.0.0.1', '--port=9000']);
            $outputContent = ob_get_clean();

            expect($exitCode)->toBe(0);
            expect($outputContent)->toContain('Starting RoadRunner development server on http://127.0.0.1:9000');
            
            // Check that passthru command contains -o http.address= and the value
            expect($GLOBALS['last_executed_command'])->toContain('-o http.address=')
                ->and($GLOBALS['last_executed_command'])->toContain('127.0.0.1:9000');
        } finally {
            @unlink($tempDir . '/bin/' . (PHP_OS_FAMILY === 'Windows' ? 'rr.exe' : 'rr'));
            @rmdir($tempDir . '/bin');
            @unlink($tempDir . '/rr.yaml');
            @rmdir($tempDir . '/public');
            @rmdir($tempDir);
            if (is_string($originalCwd)) {
                chdir($originalCwd);
            }
            $GLOBALS['last_executed_command'] = null;
        }
    });
}
