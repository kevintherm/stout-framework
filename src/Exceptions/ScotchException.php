<?php

declare(strict_types=1);

namespace Scotch\Exceptions;

use RuntimeException;

/**
 * Base exception for all Scotch framework errors.
 *
 * @phpstan-type Context array<string, mixed>
 */
final class ScotchException extends RuntimeException
{
    /** @param Context $context */
    public function __construct(
        string $message,
        private readonly array $context = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /** @return Context */
    public function getContext(): array
    {
        return $this->context;
    }
}
