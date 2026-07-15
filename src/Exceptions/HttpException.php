<?php

declare(strict_types=1);

namespace Stout\Exceptions;

/**
 * An HTTP exception that carries a status code and optional response headers.
 *
 * Throwing this from a controller or middleware will result in the
 * ErrorMiddleware returning a response with the given status code
 * and headers applied.
 *
 * @phpstan-type Context array<string, mixed>
 */
class HttpException extends StoutException
{
    /** @var array<string, string> */
    private readonly array $headers;

    /**
     * @param array<string, string> $headers  Optional headers to add to the error response
     * @param Context               $context  Debug context (only shown when displayErrorDetails=true)
     */
    public function __construct(
        int $statusCode = 500,
        string $message = '',
        array $headers = [],
        array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $context, $statusCode, $previous);
        $this->headers = $headers;
    }

    public function getStatusCode(): int
    {
        return $this->getCode();
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
