<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class PassThroughRequestHandler implements RequestHandlerInterface
{
    public function __construct(private \Closure $next) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return ($this->next)($request);
    }
}
