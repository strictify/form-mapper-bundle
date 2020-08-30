<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Service;

use Strictify\FormMapper\Service\Comparator\DataComparatorInterface;

class Comparator implements ComparatorInterface
{
    /**
     * @psalm-var iterable<DataComparatorInterface>
     *
     * @var DataComparatorInterface[]|iterable
     */
    private $comparators;

    /**
     * @psalm-param iterable<DataComparatorInterface> $comparators
     */
    public function __construct(iterable $comparators)
    {
        $this->comparators = $comparators;
    }

    /**
     * @param mixed $first
     * @param mixed $second
     */
    public function isEqual($first, $second): bool
    {
        foreach ($this->comparators as $comparator) {
            if ($comparator->isEqual($first, $second)) {
                return true;
            }
        }

        return false;
    }
}
