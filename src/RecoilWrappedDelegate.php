<?php

namespace FriendsOfReactPHP\Http\Middleware\Psr15Adapter;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\MiddlewareStackInterface;

/**
 * @internal
 */
final class RecoilWrappedDelegate implements DelegateInterface
{
    /**
     * @var DelegateInterface
     */
    private $wrappedDelegate;

    /**
     * RecoilWrappedDelegate constructor.
     * @param DelegateInterface $wrappedDelegate
     */
    public function __construct(MiddlewareStackInterface $wrappedDelegate)
    {
        $this->wrappedDelegate = $wrappedDelegate;
    }

    public function process(ServerRequestInterface $request)
    {
        return (yield $this->wrappedDelegate->process($request));
    }
}
