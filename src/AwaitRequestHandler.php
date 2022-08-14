<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function React\Async\await;
use function React\Promise\resolve;

/**
 * @internal
 */
final class AwaitRequestHandler implements RequestHandlerInterface
{
    public function __construct(private \Closure $next) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return await(resolve(($this->next)($request)));
    }
}
