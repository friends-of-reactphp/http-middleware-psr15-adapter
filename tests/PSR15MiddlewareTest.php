<?php

namespace FriendsOfReact\Tests\Http\Middleware\Psr15Adapter;

use function Clue\React\Block\await;
use FriendsOfReact\Http\Middleware\Psr15Adapter\PSR15Middleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Factory;
use React\Http\Io\ServerRequest;
use function React\Promise\resolve;
use RingCentral\Psr7\Response;

final class PSR15MiddlewareTest extends TestCase
{
    public function testBasic()
    {
        $loop = Factory::create();
        $middleware = new PSR15Middleware($loop, PSR15TestMiddleware::class);
        $request = new ServerRequest('GET', 'https://example.com/');
        $next = function () {
            return resolve(new Response());
        };

        /** @var ResponseInterface $response */
        $response = await($middleware($request, $next), $loop, 10);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('passed', $response->getHeaderLine('X-Test'));
        self::assertSame('__DIR__:' . __DIR__ . ';__FILE__:' . __DIR__ . DIRECTORY_SEPARATOR  . 'PSR15TestMiddleware.php', (string)$response->getBody());
    }
}
