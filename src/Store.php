<?php

declare(strict_types=1);

namespace Strictify\FormMapper;

/**
 * @template T
 */
class Store
{
    /** @psalm-var T */
    private $value;

    /**
     * @psalm-param T $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @psalm-return T
     */
    public function getValue()
    {
        return $this->value;
    }
}
