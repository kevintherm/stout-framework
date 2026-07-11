<?php

declare(strict_types=1);

namespace Scotch\Tests;

use Scotch\Application;

// Define helper function to boot a test application instance
function bootTestApp(): Application
{
    $basePath = realpath(__DIR__ . '/../');
    return new Application(
        basePath: is_string($basePath) ? $basePath : __DIR__ . '/../',
    );
}

// Add Pest plugins, configuration or expectations here if needed
