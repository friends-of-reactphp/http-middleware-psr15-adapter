<?php

namespace FriendsOfReact\Tests\Http\Middleware\Psr15Adapter;

use FriendsOfReact\Http\Middleware\Psr15Adapter\PSR15Middleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\ServerRequest;
use RingCentral\Psr7\Response;
use function React\Async\async;
use function React\Async\await;
use function React\Promise\resolve;

final class PSR15MiddlewareTest extends TestCase
{
    public function testBasic()
    {
        $middleware = new PSR15Middleware(new PSR15TestMiddleware());
        $request = new ServerRequest('GET', 'https://example.com/');
        $next = function () {
            return resolve(new Response());
        };

        /** @var ResponseInterface $response */
        $response = await(async(static fn (): mixed => await($middleware($request, $next)))());

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('passed', $response->getHeaderLine('X-Test'));
        self::assertSame('__DIR__:' . __DIR__ . ';__FILE__:' . __DIR__ . DIRECTORY_SEPARATOR  . 'PSR15TestMiddleware.php', (string)$response->getBody());
    }
}
