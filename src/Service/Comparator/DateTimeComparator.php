<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Service\Comparator;

use DateTimeInterface;

class DateTimeComparator implements ComparatorInterface
{
    public function isEqual($first, $second): bool
    {
        if ($first instanceof DateTimeInterface || $second instanceof DateTimeInterface) {
            /* @noinspection TypeUnsafeComparisonInspection */
            return $first == $second;
        }

        return false;
    }
}
