# Stout PHP Framework

A lightweight, opinionated, DI-first PHP framework built on top of Slim Framework and PHP-DI, specifically optimized for production-ready deployments using RoadRunner.

Stout focuses on strict typing, explicit dependency injection, and zero-magic architecture.

---

## Core Philosophies

1. **Explicit > Magical**: No hidden facades, magic service locators, or global helpers. Dependencies are explicitly constructor-injected.
2. **Strictly Typed**: Fully compatible with PHPStan Level 10.
3. **Production Server Ready**: Deeply integrated with RoadRunner out-of-the-box (with automatic binary downloading/scaffolding).
4. **Decorated Response Factories**: Leverages decorated PSR-7 factories so developers can write fluent, expressive responses (e.g. `$response->withJson()`).

---

## Key Features

### 1. Constructor-Based Dependency Injection (PHP-DI)
Register bindings explicitly inside Service Providers (`src/Providers/AppServiceProvider.php`):
```php
$builder->addDefinitions([
    UserRepositoryInterface::class => \DI\autowire(DatabaseUserRepository::class),
]);
```

### 2. Group-Based Configuration Management
Configuration files are isolated into logical groups and loaded explicitly using `.env` validation:
```php
$app->config()->loadGroup('app', require __DIR__ . '/../config/app.php');
```

### 3. Timezone-Aware PSR-3 Logging
Stout includes a clean, file-based Logger that reads the `app.timezone` setting automatically to format log timestamps in the correct timezone.

### 4. Custom CLI Engine
A lightweight, fast command-line engine to register and invoke console commands.
```php
$app = new Application(
    commands: [
        GreetCommand::class,
    ]
);
```

---

## Getting Started

### 1. Install Dependencies
```bash
cd skeleton
composer install
```

### 2. Configure Environment
```bash
cp .env.example .env
```

### 3. Set Up RoadRunner
Download the RoadRunner binary and scaffold configuration files automatically:
```bash
./vendor/bin/stout rr:install
```

### 4. Start the Application
To run using RoadRunner (default):
```bash
./vendor/bin/stout serve
```

To run using the PHP built-in server:
```bash
./vendor/bin/stout serve --php
```

### 5. Running Tests & Static Analysis
```bash
./vendor/bin/pest
./vendor/bin/phpstan analyse
```
