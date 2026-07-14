# Stout Framework - LLM Agent Guide

This guide is optimized for LLM agents working on applications built using the Stout PHP Framework. It describes core architectures, type constraints, naming patterns, and common recipes.

---

## 1. Core Architectural Constraints

- **Strict Typing (PHPStan Level 10)**: All code must pass max-level PHPStan checks.
  - Verify every return value from `file_get_contents`, `json_decode`, and `require` using type guards (`is_string`, `is_array`, etc.) or PHPDoc annotations.
  - Avoid `mixed` types. Use exact annotations, e.g. `/** @var array<string, mixed> $configData */`.
- **DI-First Philosophy**: Never resolve services dynamically via container getters inside business logic. Always inject interfaces/dependencies via class constructors.
- **No Global Helpers / Facades**: There are no `config()`, `logger()`, or `app()` global helpers. Access everything via constructor injection.

---

## 2. Framework Architecture & Entry Points

- **`Stout\Application`**: The central coordinator managing config loading, DI compiling, CLI executions, and HTTP dispatching.
- **User-defined entrypoint** (e.g. `app.php`): The application wiring script that instantiates `Application`, registers providers/commands/routes, and then runs in the appropriate mode based on `PHP_SAPI`:
  ```php
  if (PHP_SAPI === 'cli') {
      exit($app->runCli($argv));
  }
  $app->run(); // RoadRunner HTTP worker
  ```
- **There is no `bin/stout` binary.** CLI commands are invoked directly via `php app.php <command>`.
- **`rr.yaml`**: RoadRunner config points `server.command` to the same entrypoint (`php app.php`).

---

## 3. Implementation Recipes for Agents

### Recipe A: Returning Advanced HTTP Responses
Never instantiate response factories directly in controllers. Type-hint `Psr\Http\Message\ResponseFactoryInterface` in the constructor. Because the framework binds this to a `DecoratedResponseFactory`, you can type-cast or use fluent methods on the returned response:

```php
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\Response;

final class UserController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory
    ) {}

    public function __invoke(ServerRequestInterface $request, Response $_): ResponseInterface
    {
        /** @var Response $response */
        $response = $this->responseFactory->createResponse(200);

        return $response->withJson([
            'status' => 'success',
            'data' => ['id' => 1]
        ]);
    }
}
```

### Recipe B: Creating Custom Console Commands
Create a command class extending `Stout\Console\Command`. You can constructor-inject any services. Register it in the `commands` array when constructing `Application` in your entrypoint:

```php
namespace App\Commands;

use Stout\Console\Command;

final class GreetCommand extends Command
{
    public function name(): string
    {
        return 'greet';
    }

    public function description(): string
    {
        return 'Greet someone';
    }

    public function execute(array $args): int
    {
        echo "Hello, World!\n";
        return 0;
    }
}
```

### Recipe C: Registering Service Providers
Service providers must extend `Stout\Support\ServiceProvider`. Register the provider in the `providers` array when constructing `Application` in your entrypoint:

```php
namespace App\Providers;

use DI\ContainerBuilder;
use Stout\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(ContainerBuilder $builder): void
    {
        $builder->addDefinitions([
            MyInterface::class => \DI\autowire(MyImplementation::class),
        ]);
    }
}
```

---

## 4. Key Gotchas to Avoid

1. **Empty / Catchless Try Blocks**: In PHP, `try` blocks MUST have a corresponding `catch` or `finally`. Never write a `try` block with only braces closed.
2. **Missing PSR-4 Autoloading Rules**: When adding a new class, ensure namespaces map exactly to the folder structure:
   - Framework codebase: `Stout\` -> `core/src/`
   - Application codebase: `App\` -> `skeleton/src/`
3. **Pest Testing**: Always boot the test application in Pest using the defined `App\Tests\bootApp()` helper.
4. **Environment Variables**: Dotenv variables (`$_ENV`) must only be read in configuration files (`config/*.php` or `Application.php`). Use `Stout\Config\Config` everywhere else.
