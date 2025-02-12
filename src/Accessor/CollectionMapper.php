<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use ReflectionFunction;
use Symfony\Component\Form\FormInterface;
use function is_array;
use function is_callable;

class CollectionMapper extends AbstractMapper
{
    public function read(array $options, $data, FormInterface $form): array
    {
        /** @psalm-var mixed $values */
        $values = parent::read($options, $data, $form);
        if (!is_array($values)) {
            return [];
        }

        return $values;
    }

    public function update(array $options, mixed &$data, FormInterface $form): void
    {
        $originalValues = $this->read($options, $data, $form);
        $submittedData = $form->getData();
        $compare = $options['compare'];
        $adder = $options['add_value'];
        $remover = $options['remove_value'];
        $toAdd = $this->getExtraValues($compare, $originalValues, $submittedData);
        $toRemove = $this->getExtraValues($compare, $submittedData, $originalValues);

        $adderReflection = new ReflectionFunction($adder);
        $removerReflection = new ReflectionFunction($remover);

        foreach ($toAdd as $item) {
            $this->submit($data, $item, $adderReflection);
        }

        $removeEntry = $options['entry_options']['remove_entry'] ?? null;
        foreach ($toRemove as $item) {
            if (is_callable($removeEntry)) {
                $removeEntry($item);
            }
            $this->submit($data, $item, $removerReflection);
        }
    }

    private function submit(mixed $data, mixed $submittedData, ReflectionFunction $reflection): bool
    {
        $params = $reflection->getParameters();

        // if closure doesn't have params, it is equivalent of mapped: false but only for writer
        if (!$firstParam = $params[0] ?? null) {
            return false;
        }
        $reflectionType = $firstParam->getType();

        // first param does not accept null value; do not submit it
        if (null === $submittedData && $reflectionType && !$reflectionType->allowsNull()) {
            return false;
        }

        $secondParam = $params[1] ?? null;

        // user doesn't need base data; form can still be submitted
        if (!$secondParam) {
            $reflection->invoke($submittedData);

            return true;
        }

        if (null === $data && !$secondParam->allowsNull()) {
            return false;
        }

        $this->doCall($reflection, $submittedData, $data);

        return true;
    }

    /**
     * @param array<array-key, object|array> $originalValues
     * @param array<array-key, object|array> $submittedValues
     *
     * @return array<array-key, object|array|null>
     */
    private function getExtraValues(callable $compare, array $originalValues, array $submittedValues): array
    {
        /** @var array<array-key, object|array> $extraValues */
        $extraValues = [];
        foreach ($submittedValues as $key => $value) {
//            $searchKey = array_search($value, $originalValues, true);
            $searchKey = $this->search($compare, $key, $value, $originalValues);

            if (false === $searchKey || $key !== $searchKey || !$this->isEqual($compare, $originalValues[$searchKey] ?? null, $value)) {
                $extraValues[$key] = $value;
            }
        }

        return $extraValues;
    }

    private function search(callable $compare, int|string $key, mixed $value, object|array $originalValues): false|int|string
    {
        foreach ($originalValues as $originalKey => $originalValue) {
            if ($value && $originalValue && $compare($value, $originalValue, $key, $originalKey) === true) {
                return $key;
            }
        }

        return false;
    }
}
