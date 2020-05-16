<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Service;

use Strictify\FormMapper\Service\Comparator\ComparatorInterface;

class Comparator implements AccessorInterface
{
    /**
     * @var ComparatorInterface[]|iterable
     *
     * @psalm-var iterable<array-key, ComparatorInterface>
     */
    private $comparators;

    /**
     * @psalm-param iterable<array-key, ComparatorInterface> $comparators
     */
    public function __construct(iterable $comparators)
    {
        $this->comparators = $comparators;
    }
}
