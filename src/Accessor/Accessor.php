<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use Closure;
use LogicException;
use ReflectionFunction;
use function gettype;
use function trigger_error;

class Accessor
{
    /**
     * @param mixed $data
     *
     * @return mixed
     */
    public function read(Closure $getter, $data, bool $isCollection)
    {
        $reflection = new ReflectionFunction($getter);
        $params = $reflection->getParameters();

        $firstParam = $params[0] ?? null;
        // no parameter provided, return default
        if (!$firstParam) {
            return $this->getDefault($isCollection);
        }

        // still no data (example: factory failure) but nullable is not allowed; return default as well
        if (null === $data && !$firstParam->allowsNull()) {
            return $this->getDefault($isCollection);
        }

        return $getter($data);
    }

    /**
     * @param mixed $data
     * @param mixed $submittedData
     */
    public function write(Closure $getter, Closure $updater, $data, $submittedData): bool
    {
        if (null === $data) {
            return false;
        }
        $originalValue = $this->read($getter, $data, false);
        if ($this->isEqual($originalValue, $submittedData)) {
            return true;
        }

        $reflection = new ReflectionFunction($updater);
        $params = $reflection->getParameters();

        $firstParam = $params[0] ?? null;
        if (!$firstParam) {
            throw new LogicException('Method "update_value" must have parameter for submitted value.');
        }

        $type = $firstParam->getType();
        if (!$type) {
            @trigger_error('Method "update_value" should have typehint for first parameter.');
            $updater($submittedData, $data);

            return true;
        }

        if (gettype($submittedData) !== $type->getName()) {
            return false;
        }

        $updater($submittedData, $data);

        return true;
    }

    public function writeCollection(): void
    {
        throw new LogicException('N/a');
    }

    /**
     * @param mixed $originalValue
     * @param mixed $submittedValue
     */
    private function isEqual($originalValue, $submittedValue): bool
    {
        return $originalValue === $submittedValue;
    }

    /**
     * @return mixed
     */
    private function getDefault(bool $isCollection)
    {
        return $isCollection ? [] : null;
    }
}
