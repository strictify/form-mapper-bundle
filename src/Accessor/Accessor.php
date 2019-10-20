<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use DateTimeInterface;
use Traversable;
use function array_search;
use function is_iterable;
use function iterator_to_array;

class Accessor implements AccessorInterface
{
    public function update($entity, $newValue, array $config): void
    {
        $oldValue = $config['get_value']($entity);
        $isMultiple = is_iterable($oldValue);

        if (!$isMultiple) {
            $this->setSingle($oldValue, $newValue, $config['update_value'], $entity);

            return;
        }

        $this->setMultiple($entity, $oldValue, $newValue, $config['add_value'], $config['remove_value']);
    }

    private function setMultiple($entity, $oldValues, $newValues, callable $adder, callable $remover): void
    {
        $addedValues = $this->getExtraValues($oldValues, $newValues);
        $removedValues = $this->getExtraValues($newValues, $oldValues);
        foreach ($addedValues as $value) {
            $adder($value, $entity);
        }
        foreach ($removedValues as $value) {
            $remover($value, $entity);
        }
    }

    private function setSingle($oldValue, $newValue, callable $setter, $entity): void
    {
        if ($this->isEqual($oldValue, $newValue)) {
            return;
        }
        $setter($newValue, $entity);
    }

    private function getExtraValues(iterable $originalValues, array $submittedValues): array
    {
        if ($originalValues instanceof Traversable) {
            $originalValues = iterator_to_array($originalValues, true);
        }

        $extraValues = [];
        foreach ($submittedValues as $key => $value) {
            $searchKey = array_search($value, $originalValues, true);

            if (false === $searchKey || $key !== $searchKey || !$this->isEqual($submittedValues[$searchKey], $value)) {
                $extraValues[$key] = $value;
            }
        }

        return $extraValues;
    }

    private function isEqual($oldValue, $newValue): bool
    {
        if ($oldValue === $newValue) {
            return true;
        }
        if ($oldValue instanceof DateTimeInterface || $newValue instanceof DateTimeInterface) {
            /* @noinspection TypeUnsafeComparisonInspection */
            return $oldValue == $newValue;
        }

        return false;
    }
}
