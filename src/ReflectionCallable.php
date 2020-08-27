<?php

namespace Mpyw\EloquentHasByNonDependentSubquery;

use Closure;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Class ReflectionCallable
 */
class ReflectionCallable
{
    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @param  callable                              $callable
     * @return \ReflectionFunction|\ReflectionMethod
     */
    public static function from(callable $callable)
    {
        if (\is_string($callable) && \strpos($callable, '::')) {
            $callable = \explode('::', $callable);
        } elseif (!$callable instanceof Closure && \is_object($callable)) {
            $callable = [$callable, '__invoke'];
        }

        /* @noinspection PhpUnhandledExceptionInspection */
        return $callable instanceof Closure || \is_string($callable)
            ? new ReflectionFunction($callable)
            : new ReflectionMethod($callable[0], $callable[1]);
    }
}
