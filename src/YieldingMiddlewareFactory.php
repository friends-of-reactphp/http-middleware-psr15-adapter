<?php

namespace FriendsOfReact\Http\Middleware\Psr15Adapter;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\String_;
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
                $file = realpath($class::getLoader()->findFile($middleware));
                break;
            }
        }

        if (!isset($file)) {
            throw new \Exception('Could not find composer loader');
        }

        $dir = realpath(dirname($file));
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse(file_get_contents($file));
        $stmts = self::iterateStmts($stmts, $dir, $file);
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

    private static function iterateStmts(array $stmts, string $dir, string $file): array
    {
        foreach ($stmts as &$stmt) {
            if ($stmt instanceof Class_) {
                $stmt->implements = [];
            }

            if ($stmt instanceof ClassMethod && (string)$stmt->name === 'process') {
                $stmt->returnType = null;
                $stmt->params[1]->type = null;
            }

            if (isset($stmt->stmts)) {
                $stmt->stmts = static::iterateStmts($stmt->stmts, $dir, $file);
            }

            $stmt = static::checkStmt($stmt, $dir, $file);
        }
        return $stmts;
    }

    private static function checkStmt($stmt, string $dir, string $file)
    {
        if (isset($stmt->stmts)) {
            $stmt->stmts = static::iterateStmts($stmt->stmts, $dir, $file);
        }

        if (isset($stmt->expr)) {
            $stmt->expr = static::checkStmt($stmt->expr, $dir, $file);
        }

        if (isset($stmt->args)) {
            $stmt->args = static::iterateStmts($stmt->args, $dir, $file);
        }

        if ($stmt instanceof MethodCall) {
            if ($stmt->var instanceof Variable && $stmt->var->name == 'handler' && $stmt->name == 'handle') {
                return new Yield_($stmt);
            }
            $stmt->var = static::checkStmt($stmt->var, $dir, $file);
            return $stmt;
        }

        if ($stmt instanceof Dir) {
            return new String_($dir);
        }

        if ($stmt instanceof File) {
            return new String_($file);
        }

        if ($stmt instanceof Concat) {
            $stmt->left = self::checkStmt($stmt->left, $dir, $file);
            $stmt->right = self::checkStmt($stmt->right, $dir, $file);
            return $stmt;
        }

        if ($stmt instanceof Arg) {
            $stmt->value = self::checkStmt($stmt->value, $dir, $file);
            return $stmt;
        }

        return $stmt;
    }
}
