<?php

declare(strict_types=1);

namespace Stout\Http\Factory;

use Slim\Http\Factory\DecoratedResponseFactory as SlimDecoratedResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Stout\Http\Response;

class DecoratedResponseFactory extends SlimDecoratedResponseFactory
{
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        $response = parent::createResponse($code, $reasonPhrase);
        return new Response($response, $this->streamFactory);
    }
}
