<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PSR15MiddlewareInterface;
use React\Promise\PromiseInterface;
use function React\Async\async;

final class PSR15Middleware
{
    public function __construct(private PSR15MiddlewareInterface $middleware) {}

    public function __invoke(ServerRequestInterface $request, callable $next): PromiseInterface
    {
        return async(
            fn (): ResponseInterface => $this->middleware->process($request, new AwaitRequestHandler($next))
        )($request, $next);
    }
}
