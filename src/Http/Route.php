<?php

declare(strict_types=1);

namespace Stout\Http;

final class Route
{
    /** @var array<callable|\Psr\Http\Server\MiddlewareInterface|string> */
    private array $middleware = [];
    private ?string $name = null;

    /**
     * @param array<string> $methods
     * @param array{class-string, string}|callable|string $callable
     */
    public function __construct(
        private readonly array $methods,
        private readonly string $pattern,
        private readonly mixed $callable
    ) {}

    /**
     * Add middleware to this specific route.
     *
     * @param callable|\Psr\Http\Server\MiddlewareInterface|string $middleware
     */
    public function add(callable|object|string $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Set a unique name for this route (useful for URL generation).
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return array{class-string, string}|callable|string
     */
    public function getCallable(): mixed
    {
        return $this->callable;
    }

    /**
     * @return array<callable|\Psr\Http\Server\MiddlewareInterface|string>
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
