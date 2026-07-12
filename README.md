# Stout Core - Framework Documentation

This is the core library of the **Stout PHP Framework**, a lightweight, opinionated, dependency-injection-first framework built on top of the Slim 4 HTTP engine, PHP-DI, and Spiral RoadRunner.

---

## Technical Overview

Stout enforces strict type safety (PHPStan Level 10), explicit constructor-based dependency injection, and zero-magic architecture.

### Architectural Blueprint

```text
  [ bin/stout or worker.php ]
               │
               ▼
      [ bootstrap/app.php ]
               │
               ▼
     [ Stout\Application ]
      ├───► [ Stout\Config\Config ]
      ├───► [ Stout\Container\ContainerFactory ] ───► [ PHP-DI Container ]
      ├───► [ Stout\Http\Kernel ]
      └───► [ Stout\Console\Kernel ]
```

---

## Core Components

### 1. `Stout\Application`
The central registry and lifecycle coordinator of the framework. It handles:
- Loading the environment (`.env`) via `vlucas/phpdotenv`.
- Instantiating and managing the PHP-DI container.
- Bootstrapping the HTTP and Console kernels.
- Registering Service Providers and routing commands.

### 2. `Stout\Config\Config`
A mutable configuration manager supporting dot-notation retrieval and runtime merging.
- **Dot-Notation Access**: Retrieve nested values easily (e.g., `$config->get('app.timezone')`).
- **Required Configuration**: The `require()` method throws a `StoutException` if a key is missing or null, preventing silent configuration failures.
- **Group Loading**: Use `loadGroup(string $group, array $values)` to register specific group files.

### 3. `Stout\Container\ContainerFactory`
Handles compile-time setup of the `DI\ContainerBuilder`:
- Automates the registration of application-level service providers.
- Binds global default definitions, including `Config`, `Application`, and `ResponseFactoryInterface`.

### 4. `Stout\Http\Kernel` & `Stout\Http\Router`
A wrapper around the Slim 4 App instance:
- **Decorated Response Factory**: Binds `Psr\Http\Message\ResponseFactoryInterface` to Slim's `DecoratedResponseFactory` to allow fluent responses:
  ```php
  return $response->withJson(['status' => 'ok']);
  ```
- **Routing Engine**: Exposes a clean router wrapper to bind controller class names and route closures.
- **Error Handling**: Implements a dedicated `ErrorMiddleware` that formats unhandled exceptions as structured JSON responses.

### 5. `Stout\Console\Kernel` & Commands
A custom, lightweight console implementation:
- **`ListCommand`**: Displays the application ASCII banner with version parsed from `composer.json`, and lists all registered commands.
- **`ServeCommand`**: Starts the RoadRunner application server by default. Automatically triggers binary download and scaffolding if not present. Falls back to the PHP built-in server via `./vendor/bin/stout serve --php`.

### 6. `Stout\Log\Logger`
A timezone-aware PSR-3 compliant file logger:
- Extends `Psr\Log\AbstractLogger`.
- Resolves the configured `app.timezone` from the `Config` instance to correctly format date strings before writing to the log file.

### 7. `Stout\Exceptions\StoutException`
The base exception class for all framework-level runtime errors, supporting structured `context` arrays for enhanced log tracing.

---

## Static Analysis & Testing

The core framework enforces strict quality gates:
- **PHPStan**: Enforced at **Level 10** with strict rules.
- **Pest PHP**: Standard suite verifying configuration parsing, application booting, and log writing.

Run tests and analysis directly within the directory:
```bash
composer analyse
composer test
```
