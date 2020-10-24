<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use ReflectionFunction;
use Strictify\FormMapper\Store;
use Symfony\Component\Form\FormInterface;
use function strpos;
use function is_array;
use function array_search;

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

    public function update(array $options, &$data, FormInterface $form, ?Store $store): void
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
        foreach ($toRemove as $item) {
            $this->submit($data, $item, $removerReflection);
        }
    }

    /**
     * @param mixed $data
     * @param mixed $submittedData
     */
    private function submit($data, $submittedData, ReflectionFunction $reflection): bool
    {
        $params = $reflection->getParameters();

        // if there are no params, do not make a call.
        if (0 === count($params)) {
            return false;
        }
        // if closure doesn't have params, it is equivalent of mapped: false but only for writer
        $firstParam = $params[0] ?? null;
        if (!$firstParam) {
            return false;
        }
        $type = $firstParam->getType();

        // first param does not accept submitted null value; do not submit it
        if (null === $submittedData && $type && !$type->allowsNull()) {
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

        try {
            $reflection->invoke($submittedData, $data);
        } catch (\Error $e) {
            $message = $e->getMessage();
            if (strpos($message, 'must not be accessed before initialization') !== false) {
                return false;
            }
            throw $e;
        }

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
            $searchKey = array_search($value, $originalValues, true);

            if (false === $searchKey || $key !== $searchKey || !$this->isEqual($compare, $submittedValues[$searchKey], $value)) {
                $extraValues[$key] = $value;
            }
        }

        return $extraValues;
    }
}
