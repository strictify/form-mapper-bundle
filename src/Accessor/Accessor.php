<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use Strictify\FormMapper\Accessor\Comparator\ComparatorInterface;
use Generator;
use Traversable;
use function array_search;
use function is_iterable;
use function iterator_to_array;

class Accessor implements AccessorInterface
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

    public function update($object, $newValue, array $config): void
    {
        $getter = $config['get_value'];
        $oldValue = $getter($object);
        $isMultiple = is_iterable($oldValue) || is_iterable($newValue);

        if (!$isMultiple) {
            $this->setSingle($object, $oldValue, $newValue, $config['update_value']);

            return;
        }

        $this->setMultiple($object, $oldValue, $newValue, $config['add_value'], $config['remove_value']);
    }

    /**
     * @param array|object $object
     *
     * @psalm-param iterable<array-key, mixed> $oldValues
     * @psalm-param iterable<array-key, mixed> $newValues
     */
    private function setMultiple($object, iterable $oldValues, iterable $newValues, callable $adder, callable $remover): void
    {
        $addedValues = $this->getExtraValues($oldValues, $newValues);
        $removedValues = $this->getExtraValues($newValues, $oldValues);

        foreach ($removedValues as $value) {
            $remover($value, $object);
        }
        foreach ($addedValues as $value) {
            $adder($value, $object);
        }
    }

    /**
     * @param mixed $object
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    private function setSingle($object, $oldValue, $newValue, callable $setter): void
    {
        if ($this->isEqual($oldValue, $newValue)) {
            return;
        }
        $setter($newValue, $object);
    }

    private function getExtraValues(iterable $originalValues, iterable $submittedValues): Generator
    {
        if ($originalValues instanceof Traversable) {
            $originalValues = iterator_to_array($originalValues, true);
        }
        if ($submittedValues instanceof Traversable) {
            $submittedValues = iterator_to_array($submittedValues, true);
        }

        foreach ($submittedValues as $key => $value) {
            /** @psalm-var array-key|false $searchKey */
            $searchKey = array_search($value, $originalValues, true);

            if (false === $searchKey || $key !== $searchKey || !$this->isEqual($submittedValues[$searchKey], $value)) {
                yield $value;
            }
        }
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    private function isEqual($oldValue, $newValue): bool
    {
        // simple comparison first
        if ($oldValue === $newValue) {
            return true;
        }

        foreach ($this->comparators as $comparator) {
            if ($comparator->isEqual($oldValue, $newValue)) {
                return true;
            }
        }

        return false;
    }
}
