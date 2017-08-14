<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use Interop\Http\ServerMiddleware\MiddlewareInterface as PSR15MiddlewareInterface;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Http\MiddlewareInterface;
use React\Http\MiddlewareStackInterface;
use React\Promise;
use Recoil\React\ReactKernel;

final class PSR15Middleware implements MiddlewareInterface
{
    /**
     * @var ReactKernel
     */
    private $kernel;

    /**
     * @var PSR15MiddlewareInterface
     */
    private $middleware;


    public function __construct(LoopInterface $loop, $middleware, array $arguments = [])
    {
        $this->kernel = ReactKernel::create($loop);
        $this->middleware = $this->buildYieldingMiddleware($middleware, $arguments);
    }

    public function process(ServerRequestInterface $request, MiddlewareStackInterface $stack)
    {
        return new Promise\Promise(function ($resolve, $reject) use ($request, $stack) {
            $this->kernel->execute(function () use ($resolve, $reject, $request, $stack) {
                try {
                    $response = (yield $this->middleware->process($request, new RecoilWrappedDelegate($stack)));
                    $resolve($response);
                } catch (\Throwable $throwable) {
                    $reject($throwable);
                }
            });
        });
    }

    private function buildYieldingMiddleware($middleware, array $arguments)
    {
        if (!is_subclass_of($middleware, PSR15MiddlewareInterface::class)) {
            throw new \Exception('Not a PSR15 middleware');
        }

        foreach (get_declared_classes() as $class) {
            if (strpos($class, 'ComposerAutoloaderInit') === 0) {
                $file = $class::getLoader()->findFile($middleware);
            }
        }

        if (!isset($file)) {
            throw new \Exception('Could not find composer loader');
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse(file_get_contents($file));
        $stmts = $this->iterateStmts($stmts);
        $prettyPrinter = new Standard();
        $code = $prettyPrinter->prettyPrint($stmts);

        $namespace = explode('\\', $middleware);
        $className = array_pop($namespace);
        $newClassName = str_replace('.', '_', uniqid($className . '_', true));
        $FQCN = implode('\\', $namespace) . '\\' . $newClassName;
        $code = str_replace('class ' . $className, 'class ' . $newClassName, $code);
        eval($code);
        return new $FQCN(...$arguments);
    }

    private function iterateStmts($stmts)
    {
        foreach ($stmts as &$stmt) {
            if (isset($stmt->stmts)) {
                $stmt->stmts = $this->iterateStmts($stmt->stmts);
            }

            $stmt = $this->checkStmt($stmt);
        }
        return $stmts;
    }

    private function checkStmt($stmt)
    {
        if (isset($stmt->stmts)) {
            $stmt->stmts = $this->iterateStmts($stmt->stmts);
        }

        if (isset($stmt->expr)) {
            $stmt->expr = $this->checkStmt($stmt->expr);
            return $stmt;
        }

        if ($stmt instanceof MethodCall) {
            if ($stmt->var instanceof Variable && $stmt->var->name == 'delegate' && $stmt->name == 'process') {
                return new Yield_($stmt);
            }
            $stmt->var = $this->checkStmt($stmt->var);
            return $stmt;
        }

        return $stmt;
    }
}
