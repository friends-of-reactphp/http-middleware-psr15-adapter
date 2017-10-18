# A PSR-15 middleware adapter for react/http

Wraps PSR-15 middleware into coroutines using [`RecoilPHP`](https://github.com/recoilphp) making them usable within `react/http` as middleware.

# Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require for/http-middleware-psr15-adapter
```

# Usage

The following usage example uses [`middlewares/redirect`](https://github.com/middlewares/redirect) adding one redirect, 
and using the callback to call several methods on the redirect middleware to change it's behavior:

```php
$loop = Factory::create(); 
$server = new Server(new MiddlewareRunner([
    /** Other middleware */
    new PSR15Middleware(
        $loop, // The react/event-loop (required) 
        Redirect::class, // String class name of the middleware (required)
        [ // Any constructor arguments (optional)
            ['/old-url' => '/new-url']
        ],
        function ($redirectMiddleware) {
            // This callback is optional, but when used it must return the
            // instance passed into it, or a clone of it.
            return $redirectMiddleware
                ->permanent(false)
                ->query(false)
                ->method(['GET', 'POST'])
            ;
        }
    ),
    /** Other middleware */
]));
```

# Warning

This adapter rewrite the code of the PSR-15 middleware during the constructor phase, wrapping all `$delegate->process($request)`
calls into a yield `(yield $delegate->process($request))`. This should work for most middleware but cannot be guaranteed for all.
In case you run into issues please open an issue with the middleware in question you're having problems with.
