<?php

declare(strict_types=1);

namespace Stout\Http;

use Slim\Http\Response as SlimResponse;

class Response extends SlimResponse
{
    /**
     * Return a JSON response.
     */
    public function json(mixed $data, int $status = 200): self
    {
        return $this->withJson($data)->withStatus($status);
    }

    /**
     * Return an HTML response.
     */
    public function html(string $html, int $status = 200): self
    {
        $this->getBody()->write($html);
        return $this->withHeader('Content-Type', 'text/html; charset=utf-8')->withStatus($status);
    }

    /**
     * Return a plain text response.
     */
    public function text(string $text, int $status = 200): self
    {
        $this->getBody()->write($text);
        return $this->withHeader('Content-Type', 'text/plain; charset=utf-8')->withStatus($status);
    }

    /**
     * Redirect to a URL.
     */
    public function redirect(string $url, int $status = 302): self
    {
        return $this->withHeader('Location', $url)->withStatus($status);
    }
}
