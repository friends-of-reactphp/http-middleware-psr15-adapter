# A PSR-15 middleware adapter for react/http

[![CI status](https://github.com/friends-of-reactphp/http-middleware-psr15-adapter/workflows/CI/badge.svg)](https://github.com/friends-of-reactphp/http-middleware-psr15-adapter/actions)

Wraps PSR-15 middleware using `async` and `await` from `react/async` utilizing fibers making them usable within `react/http` as middleware.

# Install

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require for/http-middleware-psr15-adapter
```

# Usage

The following usage example uses [`middlewares/redirect`](https://github.com/middlewares/redirect) adding one redirect, 
and using the callback to call several methods on the redirect middleware to change it's behavior:

```php
$server = new Server(
    /** Other middleware */
    new PSR15Middleware(
        (new Redirect(['/old-url' => '/new-url']))->permanent(false)->query(false)->method(['GET', 'POST'])
    ),
    /** Other middleware */
);
```

# Grouped Usage

When using more then one PSR-15 in a row the `GroupedPSR15Middleware` is more performing than using multiple `PSR15Middleware`. Consider the 
following example where we add [`middlewares/cache`](https://github.com/middlewares/cache) for expires headers:

```php
$loop = Factory::create(); 
$server = new Server([
    /** Other middleware */
    (new GroupedPSR15Middleware($loop))->withMiddleware( 
        (new Redirect(['/old-url' => '/new-url']))->permanent(false)->query(false)->method(['GET', 'POST'])
    )->withMiddleware(
        new Expires()
    ),
    /** Other middleware */
]);
```
