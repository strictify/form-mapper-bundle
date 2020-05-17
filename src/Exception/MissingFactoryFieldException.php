<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Exception;

use OutOfBoundsException;
use Throwable;

class MissingFactoryFieldException extends OutOfBoundsException implements FactoryExceptionInterface
{
    public function __construct(string $message, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
