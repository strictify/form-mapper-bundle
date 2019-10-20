<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Service;

use InvalidArgumentException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use Closure;
use function is_array;

class CallableReader implements CallableReaderInterface
{
    public function getReflection(callable $callable): ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        /* @noinspection CallableParameterUseCaseInTypeContextInspection */
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        throw new InvalidArgumentException('Unsupported callable, use Closures or [$object, "method"] syntax.');
    }
}
