<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Exception;

use OutOfBoundsException;
use Throwable;

class MissingFactoryFieldException extends OutOfBoundsException implements FactoryExceptionInterface
{
    private string $field;

    public function __construct(string $message, string $field, int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->field = $field;
    }

    public function getField(): string
    {
        return $this->field;
    }
}
