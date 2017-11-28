<?php

namespace FriendsOfReact\Tests\Http\Middleware\Psr15Adapter;

use Interop\Http\Server\MiddlewareInterface as PSR15MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PSR15TestMiddleware implements PSR15MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $response = $response->withHeader('X-Test', 'passed');
        return $response;
    }
}
