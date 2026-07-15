<?php

declare(strict_types=1);

namespace Stout\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpSpecializedException;
use Stout\Contracts\HttpErrorRenderer;
use Stout\Exceptions\HttpException;
use Throwable;

final readonly class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private HttpErrorRenderer $errorRenderer,
        private bool $displayErrorDetails = false,
        private bool $logErrors = false,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            if ($this->logErrors) {
                $this->logger->error(sprintf(
                    '%s %s: %s',
                    $request->getMethod(),
                    $request->getUri()->getPath(),
                    $exception->getMessage()
                ), [
                    'exception' => $exception,
                ]);
            }

            $statusCode = $this->resolveHttpStatusCode($exception);

            $response = $this->errorRenderer->render(
                $exception,
                $statusCode,
                $request,
                $this->displayErrorDetails,
            );

            if ($exception instanceof HttpException) {
                foreach ($exception->getHeaders() as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }
            }

            return $response;
        }
    }

    /**
     * Resolve the appropriate HTTP status code from the thrown exception.
     *
     * Slim's HttpSpecializedException subclasses (HttpNotFoundException, etc.)
     * carry the correct status code via getCode(). For any other exception,
     * defaults to 500 Internal Server Error.
     */
    private function resolveHttpStatusCode(Throwable $exception): int
    {
        if ($exception instanceof HttpSpecializedException) {
            return $exception->getCode();
        }

        $code = $exception->getCode();

        if (is_int($code) && $code >= 400 && $code < 600) {
            return $code;
        }

        return 500;
    }
}
