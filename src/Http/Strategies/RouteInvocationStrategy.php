<?php

declare(strict_types=1);

namespace Stout\Http\Strategies;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;
use Stout\Exceptions\StoutException;

/**
 * Route invocation strategy that handles returning responses, HTML strings, or JSON-serializable data.
 */
class RouteInvocationStrategy implements InvocationStrategyInterface
{
    /**
     * @param array<string, string> $routeArguments
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        foreach ($routeArguments as $k => $v) {
            $request = $request->withAttribute($k, $v);
        }

        $result = $callable($request, $response, $routeArguments);

        if ($result instanceof ResponseInterface) {
            return $result;
        }

        if (is_string($result)) {
            if ($response instanceof \Stout\Http\Response) {
                return $response->html($result);
            }
            $response->getBody()->write($result);
            return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        }

        if (is_array($result) || $result instanceof \JsonSerializable || $result instanceof \stdClass) {
            if ($response instanceof \Stout\Http\Response) {
                return $response->json($result);
            }
            $response->getBody()->write((string) json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        }

        if ($result === null) {
            return $response;
        }

        throw new StoutException(sprintf(
            'Unexpected route callback return value of type %s.',
            get_debug_type($result)
        ));
    }
}
