<?php

declare(strict_types=1);

namespace Stout\Contracts;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Renders an HTTP error response from a thrown exception.
 *
 * Implement this interface and bind it in a service provider to
 * customize how errors are formatted (JSON, HTML, XML, etc.).
 *
 * @see \Stout\Http\Renderer\JsonErrorRenderer for the default implementation
 */
interface HttpErrorRenderer
{
    /**
     * Produce an HTTP response for the given exception.
     *
     * The returned response MUST have a body and appropriate Content-Type header.
     * The framework's ErrorMiddleware will apply HttpException headers on top.
     *
     * @param Throwable            $exception           The caught exception
     * @param int                  $statusCode          The resolved HTTP status code
     * @param ServerRequestInterface $request           The incoming request
     * @param bool                 $displayErrorDetails Whether to include debugging details
     */
    public function render(
        Throwable $exception,
        int $statusCode,
        ServerRequestInterface $request,
        bool $displayErrorDetails,
    ): ResponseInterface;
}
