<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @internal
 */
final class RecoilWrappedDelegate implements DelegateInterface
{
    /**
     * @var callable
     */
    private $next;

    /**
     * @param callable $wrappedDelegate
     */
    public function __construct($next)
    {
        $this->next = $next;
    }

    public function process(ServerRequestInterface $request)
    {
        $next = $this->next;
        return (yield $next($request));
    }
}
