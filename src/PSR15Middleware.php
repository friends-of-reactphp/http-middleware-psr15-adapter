<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PSR15MiddlewareInterface;
use React\EventLoop\LoopInterface;
use React\Promise;
use Recoil\React\ReactKernel;
use Throwable;

final class PSR15Middleware
{
    /**
     * @var ReactKernel
     */
    private $kernel;

    /**
     * @var PSR15MiddlewareInterface
     */
    private $middleware;

    public function __construct(LoopInterface $loop, string $middleware, array $arguments = [], callable $func = null)
    {
        if ($func === null) {
            $func = function ($middleware) {
                return $middleware;
            };
        }

        $this->kernel = ReactKernel::create($loop);
        $this->middleware = $func(YieldingMiddlewareFactory::construct($middleware, $arguments));
    }

    public function __invoke(ServerRequestInterface $request, callable $next): Promise\PromiseInterface
    {
        return new Promise\Promise(function ($resolve, $reject) use ($request, $next) {
            $this->kernel->execute(function () use ($resolve, $reject, $request, $next) {
                try {
                    $response = $this->middleware->process($request, new RecoilWrappedRequestHandler($next));
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
}
