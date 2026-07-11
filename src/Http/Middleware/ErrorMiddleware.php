<?php

declare(strict_types=1);

namespace Scotch\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Scotch\Exceptions\ScotchException;
use Throwable;

final readonly class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private LoggerInterface $logger,
        private bool $displayErrorDetails = false,
        private bool $logErrors = false,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $exception) {
            if ($this->logErrors) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }

            $response = $this->responseFactory->createResponse(500)
                ->withHeader('Content-Type', 'application/json');

            $payload = [
                'error' => true,
                'message' => $this->displayErrorDetails ? $exception->getMessage() : 'Internal Server Error',
            ];

            if ($this->displayErrorDetails) {
                $payload['exception'] = get_class($exception);
                $payload['trace'] = explode("\n", $exception->getTraceAsString());
                
                if ($exception instanceof ScotchException) {
                    $payload['context'] = $exception->getContext();
                }
            }

            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            
            if ($json === false) {
                $response->getBody()->write((string) json_encode(['error' => true, 'message' => $exception->getMessage()]));
            } else {
                $response->getBody()->write($json);
            }

            return $response;
        }
    }
}
