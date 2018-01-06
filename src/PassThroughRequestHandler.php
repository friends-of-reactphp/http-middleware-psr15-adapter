<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class PassThroughRequestHandler
{
    /**
     * @var callable
     */
    private $next;

    /**
     * @param callable $next
     */
    public function __construct(callable $next)
    {
        $this->next = $next;
    }

    public function handle(ServerRequestInterface $request)
    {
        $next = $this->next;
        return $next($request);
    }
}
