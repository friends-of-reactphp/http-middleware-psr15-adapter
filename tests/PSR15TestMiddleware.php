<?php

namespace FriendsOfReact\Tests\Http\Middleware\Psr15Adapter;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface as PSR15MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PSR15TestMiddleware implements PSR15MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $response = $delegate->process($request);
        $response = $response->withHeader('X-Test', 'passed');
        return $response;
    }
}
