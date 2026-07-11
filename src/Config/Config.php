<?php

declare(strict_types=1);

namespace Stout\Config;

use Stout\Exceptions\StoutException;

/**
 * Immutable configuration bag. Loaded from a flat or nested PHP array.
 * Supports dot-notation key access.
 *
 * @phpstan-type ConfigArray array<string, mixed>
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Retrieve a value by dot-notation key.
     *
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->resolve($key) ?? $default;
    }

    /**
     * Retrieve a required value — throws if the key is absent or null.
     *
     * @return mixed
     * @throws StoutException
     */
    public function require(string $key): mixed
    {
        $value = $this->resolve($key);

        if ($value === null) {
            throw new StoutException(
                message: "Required config key \"{$key}\" is missing or null.",
                context: ['key' => $key],
            );
        }

        return $value;
    }

    /** @return mixed */
    private function resolve(string $key): mixed
    {
        $segments = explode('.', $key);
        $current  = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    public function merge(self $other): self
    {
        /** @var array<string, mixed> $merged */
        $merged = array_replace_recursive($this->data, $other->data);
        return new self($merged);
    }

    /**
     * Merge in a named config group from a file or array (mutates this instance).
     * Usage in bootstrap/app.php:
     *   $app->config()->loadGroup('database', require __DIR__.'/../config/database.php');
     *
     * @param array<string, mixed> $values
     */
    public function loadGroup(string $key, array $values): void
    {
        $existing = isset($this->data[$key]) && is_array($this->data[$key]) ? $this->data[$key] : [];
        $this->data[$key] = array_replace_recursive($existing, $values);
    }

    /**
     * Return a new Config with an additional named group (immutable variant).
     *
     * @param array<string, mixed> $values
     */
    public function withGroup(string $key, array $values): self
    {
        /** @var array<string, mixed> $merged */
        $merged = array_replace_recursive($this->data, [$key => $values]);
        return new self($merged);
    }

    /**
     * Load a Config from a PHP file that returns an array.
     *
     * @throws StoutException
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new StoutException(
                message: "Config file not found: {$path}",
                context: ['path' => $path],
            );
        }

        $data = require $path;

        if (!is_array($data)) {
            throw new StoutException(
                message: "Config file must return an array: {$path}",
                context: ['path' => $path],
            );
        }

        /** @var array<string, mixed> $data */
        return new self($data);
    }
}
