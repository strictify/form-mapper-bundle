<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Service\Comparator;

interface ComparatorInterface
{
    /**
     * @param mixed $first
     * @param mixed $second
     */
    public function isEqual($first, $second): bool;
}
