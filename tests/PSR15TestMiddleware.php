<?php

namespace FriendsOfReact\Tests\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PSR15MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class PSR15TestMiddleware implements PSR15MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $response = $response->withHeader('X-Test', 'passed');
        return $response;
    }
}
