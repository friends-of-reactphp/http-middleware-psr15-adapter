<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PSR15MiddlewareInterface;
use React\EventLoop\LoopInterface;
use React\Promise;
use Recoil\React\ReactKernel;
use Throwable;

final class GroupedPSR15Middleware
{
    /**
     * @var ReactKernel
     */
    private $kernel;

    /**
     * @var PSR15MiddlewareInterface[]
     */
    private $middleware = [];

    public function __construct(LoopInterface $loop)
    {
        $this->kernel = ReactKernel::create($loop);
    }

    public function withMiddleware(string $middleware, array $arguments = [], callable $func = null)
    {
        if ($func === null) {
            $func = function ($middleware) {
                return $middleware;
            };
        }

        $clone = clone $this;
        $clone->middleware[] = $func(YieldingMiddlewareFactory::construct($middleware, $arguments));

        return $clone;
    }

    public function __invoke(ServerRequestInterface $request, callable $next): Promise\PromiseInterface
    {

        $stack = $this->createStack($next);

        return new Promise\Promise(function ($resolve, $reject) use ($request, $next, $stack) {
            $this->kernel->execute(function () use ($resolve, $reject, $request, $next, $stack) {
                try {
                    $response = $stack($request, $next);
                    if ($response instanceof ResponseInterface) {
                        $response = Promise\resolve($response);
                    }
                    $response = (yield $response);
                    $resolve($response);
                } catch (Throwable $throwable) {
                    $reject($throwable);
                }
            });
        });
    }

    private function createStack($next)
    {
        $stack = function (ServerRequestInterface $request) use ($next) {
            $response = $next($request);
            if ($response instanceof ResponseInterface) {
                $response = Promise\resolve($response);
            }
            return (yield $response);
        };

        $middleware = $this->middleware;
        $middleware = array_reverse($middleware);
        foreach ($middleware as $mw) {
            $mwh = $mw;
            $stack = function (ServerRequestInterface $request) use ($stack, $mwh) {
                $response = $mwh->process($request, new PassThroughRequestHandler($stack));
                if ($response instanceof ResponseInterface) {
                    $response = Promise\resolve($response);
                }
                return (yield $response);
            };
        }

        return $stack;
    }
}
