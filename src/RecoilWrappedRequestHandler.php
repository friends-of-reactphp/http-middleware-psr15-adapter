<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Psr\Http\Message\ServerRequestInterface;
use function React\Promise\resolve;

/**
 * @internal
 */
final class RecoilWrappedRequestHandler
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
        return (yield resolve($next($request)));
    }
}
