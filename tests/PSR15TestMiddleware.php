<?php

namespace FriendsOfReact\Tests\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PSR15MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function RingCentral\Psr7\stream_for;

final class PSR15TestMiddleware implements PSR15MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $response = $response->withHeader('X-Test', 'passed');
        $response = $response->withBody(stream_for('__DIR__:' . __DIR__ . ';__FILE__:' . __FILE__));
        return $response;
    }
}
