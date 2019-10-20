<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Service;

use ReflectionFunctionAbstract;

interface CallableReaderInterface
{
    public function isPositionTypehinted(callable $callable, int $position): bool;

    public function getReflection(callable $callable): ReflectionFunctionAbstract;
}
