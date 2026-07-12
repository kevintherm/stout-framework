<?php

declare(strict_types=1);

namespace Stout\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class RequestLifecycle
{
    /** @var array<callable(ServerRequestInterface): void> */
    private array $beforeCallbacks = [];

    /** @var array<callable(?ResponseInterface, ?Throwable): void> */
    private array $afterCallbacks = [];

    /**
     * Register a callback to run before handling the HTTP request.
     *
     * @param callable(ServerRequestInterface): void $callback
     */
    public function before(callable $callback): self
    {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }

    /**
     * Register a callback to run after handling the HTTP request.
     *
     * @param callable(?ResponseInterface, ?Throwable): void $callback
     */
    public function after(callable $callback): self
    {
        $this->afterCallbacks[] = $callback;
        return $this;
    }

    /**
     * Get all registered before-request callbacks.
     *
     * @return array<callable(ServerRequestInterface): void>
     */
    public function getBeforeCallbacks(): array
    {
        return $this->beforeCallbacks;
    }

    /**
     * Get all registered after-request callbacks.
     *
     * @return array<callable(?ResponseInterface, ?Throwable): void>
     */
    public function getAfterCallbacks(): array
    {
        return $this->afterCallbacks;
    }
}
