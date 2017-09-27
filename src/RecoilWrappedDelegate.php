<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use function React\Promise\resolve;

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
    public function __construct(callable $next)
    {
        $this->next = $next;
    }

    public function process(ServerRequestInterface $request)
    {
        $next = $this->next;
        return (yield resolve($next($request)));
    }
}
