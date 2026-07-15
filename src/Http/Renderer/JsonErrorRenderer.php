<?php

declare(strict_types=1);

namespace Stout\Http\Renderer;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stout\Contracts\HttpErrorRenderer;
use Stout\Exceptions\StoutException;
use Throwable;

/**
 * Default error renderer that produces JSON responses.
 *
 * Bind a custom HttpErrorRenderer implementation to override this.
 */
final readonly class JsonErrorRenderer implements HttpErrorRenderer
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function render(
        Throwable $exception,
        int $statusCode,
        ServerRequestInterface $request,
        bool $displayErrorDetails,
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json');

        $payload = [
            'error' => true,
            'message' => $displayErrorDetails || $statusCode < 500
                ? $exception->getMessage()
                : 'Internal Server Error',
        ];

        if ($displayErrorDetails) {
            $payload['exception'] = $exception::class;
            $payload['trace'] = explode("\n", $exception->getTraceAsString());

            if ($exception instanceof StoutException) {
                $payload['context'] = $exception->getContext();
            }
        }

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            $response->getBody()->write((string) json_encode([
                'error' => true,
                'message' => $exception->getMessage(),
            ]));
        } else {
            $response->getBody()->write($json);
        }

        return $response;
    }
}
