<?php

declare(strict_types=1);

namespace Stout\Http;

use Slim\Http\ServerRequest;

class Request extends ServerRequest
{
    /**
     * Get a query parameter.
     */
    public function query(string $key, mixed $default = null): mixed
    {
        return $this->getQueryParam($key, $default);
    }

    /**
     * Get a parsed body parameter.
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->getParsedBodyParam($key, $default);
    }

    /**
     * Get a JSON body parameter.
     */
    public function json(string $key, mixed $default = null): mixed
    {
        $body = $this->getParsedBody();
        if (is_array($body)) {
            return $body[$key] ?? $default;
        }
        return $default;
    }
}
