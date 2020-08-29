<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use Closure;
use ReflectionFunction;
use Strictify\FormMapper\Service\Comparator;
use Traversable;
use function count;
use function gettype;
use function iterator_to_array;
use function trigger_error;

class Accessor
{
    private Comparator $comparator;

    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
    }

    /**
     * @param array|object|null $data
     *
     * @return mixed
     */
    public function read(Closure $getter, $data, bool $isCollection)
    {
        $reflection = new ReflectionFunction($getter);
        $params = $reflection->getParameters();

        $firstParam = $params[0] ?? null;
        // if no first param and no data, still make a call to getter; useful with `$builder->getData()` and arrow functions.
        if (null === $firstParam && null === $data) {
            return $getter();
        }

        // still no data (example: factory failure) but nullable is not allowed; return default as well
        if (null === $data && !$firstParam->allowsNull()) {
            return $this->getDefault($isCollection);
        }

        return $getter($data);
    }

    /**
     * @param array|object|null $data
     * @param mixed $submittedData
     */
    public function write(Closure $getter, Closure $updater, $data, $submittedData): bool
    {
        /** @var array|object|null $originalValue */
        $originalValue = $this->read($getter, $data, false);
        if ($this->isEqual($originalValue, $submittedData)) {
            return true;
        }

        return $this->submit($data, $updater, $submittedData);
    }

    private function submit($data, Closure $updater, $submittedData): bool
    {
        $reflection = new ReflectionFunction($updater);
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
        if (!$type) {
            @trigger_error('Method "update_value" should have typehint for first parameter.');
        }

        // check type of first param; if not a match, don't make a call
        if (gettype($submittedData) !== $type->getName()) {
            return false;
        }

        $secondParam = $params[1] ?? null;

        // user doesn't need base data; form can still be submitted
        if (!$secondParam) {
            $updater($submittedData);

            return true;
        }

        if (null === $data && !$secondParam->allowsNull()) {
            return false;
        }

        $updater($submittedData, $data);

        return true;
    }

    /**
     * @param object|array|null $data
     * @param iterable<array-key, object|array> $submittedData
     * @param Closure(mixed, object|array|null): void $adder
     * @param Closure(mixed, object|array|null): void $remover
     */
    public function writeCollection(Closure $getter, Closure $adder, Closure $remover, $data, iterable $submittedData): bool
    {
        /** @var iterable<array-key, object|array> $originalValues */
        $originalValues = $this->read($getter, $data, false);
        $originalValues = $this->iterableToArray($originalValues);
        $submittedData = $this->iterableToArray($submittedData);

        $toAdd = $this->getExtraValues($originalValues, $submittedData);
        $toRemove = $this->getExtraValues($submittedData, $originalValues);
        foreach ($toAdd as $item) {
            $this->submit($data, $adder, $item);
//            $adder($item, $data);
        }
        foreach ($toRemove as $item) {
            $this->submit($data, $remover, $item);
//            $remover($item, $data);
        }

        return true;
    }

    /**
     * @param array<array-key, object|array> $originalValues
     * @param array<array-key, object|array> $submittedValues
     *
     * @return array<array-key, object|array>
     */
    private function getExtraValues(array $originalValues, array $submittedValues): array
    {
        /** @var array<array-key, object|array> $extraValues */
        $extraValues = [];
        foreach ($submittedValues as $key => $value) {
            $searchKey = array_search($value, $originalValues, true);

            if (false === $searchKey || $key !== $searchKey || !$this->isEqual($submittedValues[$searchKey], $value)) {
                $extraValues[$key] = $value;
            }
        }

        return $extraValues;
    }

    /**
     * @param mixed $first
     * @param mixed $second
     */
    private function isEqual($first, $second): bool
    {
        if ($first === $second) {
            return true;
        }

        return $this->comparator->isEqual($first, $second);
    }

    /**
     * @return mixed
     */
    private function getDefault(bool $isCollection)
    {
        return $isCollection ? [] : null;
    }

    /**
     * @template T
     *
     * @psalm-param iterable<array-key, T> $iterable
     *
     * @psalm-return array<array-key, T>
     */
    private function iterableToArray($iterable): array
    {
        if ($iterable instanceof Traversable) {
            return iterator_to_array($iterable, true);
        }

        return $iterable;
    }
}
