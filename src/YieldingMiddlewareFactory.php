<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Psr\Http\Server\MiddlewareInterface as PSR15MiddlewareInterface;

final class YieldingMiddlewareFactory
{
    public static function construct(string $middleware, array $arguments)
    {
        if (!is_subclass_of($middleware, PSR15MiddlewareInterface::class)) {
            throw new \Exception('Not a PSR15 middleware');
        }

        foreach (get_declared_classes() as $class) {
            if (strpos($class, 'ComposerAutoloaderInit') === 0) {
                $file = $class::getLoader()->findFile($middleware);
                break;
            }
        }

        if (!isset($file)) {
            throw new \Exception('Could not find composer loader');
        }

        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse(file_get_contents($file));
        $stmts = self::iterateStmts($stmts);
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

    private static function iterateStmts(array $stmts): array
    {
        foreach ($stmts as &$stmt) {
            if ($stmt instanceof Class_) {
                $stmt->implements = [];
            }

            if ($stmt instanceof ClassMethod && $stmt->name === 'process') {
                $stmt->returnType = null;
                $stmt->params[1]->type = null;
            }

            if (isset($stmt->stmts)) {
                $stmt->stmts = static::iterateStmts($stmt->stmts);
            }

            $stmt = static::checkStmt($stmt);
        }
        return $stmts;
    }

    private static function checkStmt($stmt)
    {
        if (isset($stmt->stmts)) {
            $stmt->stmts = static::iterateStmts($stmt->stmts);
        }

        if (isset($stmt->expr)) {
            $stmt->expr = static::checkStmt($stmt->expr);
            return $stmt;
        }

        if ($stmt instanceof MethodCall) {
            if ($stmt->var instanceof Variable && $stmt->var->name == 'handler' && $stmt->name == 'handle') {
                return new Yield_($stmt);
            }
            $stmt->var = static::checkStmt($stmt->var);
            return $stmt;
        }

        return $stmt;
    }
}
