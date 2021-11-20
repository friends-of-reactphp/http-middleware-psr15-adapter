<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PSR15MiddlewareInterface;
use React\Promise;
use function React\Async\async;

final class GroupedPSR15Middleware
{
    /**
     * @var array<PSR15MiddlewareInterface>
     */
    private array $middleware = [];

    public function withMiddleware(PSR15MiddlewareInterface $middleware): self
    {
        $clone = clone $this;
        $clone->middleware[] = $middleware;
        return $clone;
    }

    public function __invoke(ServerRequestInterface $request, callable $next): Promise\PromiseInterface
    {
        return async(function (ServerRequestInterface $request, callable $next): ResponseInterface {
            $middleware = array_reverse($this->middleware);
            $requestHandler = new AwaitRequestHandler($next);

            foreach ($middleware as $mw) {
                $requestHandler = new PassThroughRequestHandler(static fn (ServerRequestInterface $request): ResponseInterface => $mw->process($request, $requestHandler));
            }

            return $requestHandler->handle($request);
        })($request, $next);
    }
}
