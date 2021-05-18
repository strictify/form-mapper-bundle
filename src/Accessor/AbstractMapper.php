<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use Error;
use ReflectionFunction;
use InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Strictify\FormMapper\Service\Comparator;
use function strpos;

abstract class AbstractMapper implements MapperInterface
{
    public function __construct(protected Comparator $comparator)
    {
    }

    /**
     * @return mixed
     */
    public function read(array $options, $data, FormInterface $form)
    {
        $getter = $options['get_value'];
        if (!$getter) {
            throw new InvalidArgumentException('You have to assign "get_value" callable.');
        }
        $reflection = new ReflectionFunction($getter);

        // we have data; make a call to getter
        if (null !== $data) {
            return $this->doCall($reflection, $data);
        }

        $params = $reflection->getParameters();
        $firstParam = $params[0] ?? null;
        // if no first param and no data, still make a call to getter; useful with `$builder->getData()` and arrow functions.
        if (null === $firstParam) {
            return $this->doCall($reflection);
        }

        // still no data (example: factory failure) but nullable is not allowed; return default as well
        if ($firstParam->allowsNull()) {
            return $this->doCall($reflection, null);
        }

        return null;
    }

    /**
     * @param mixed $first
     * @param mixed $second
     */
    protected function isEqual(callable $compare, $first, $second): bool
    {
        if ($compare($first, $second) === true) {
            return true;
        }

        return $this->comparator->isEqual($first, $second);
    }

    /**
     * @param mixed ...$values
     *
     * @return mixed
     */
    protected function doCall(ReflectionFunction $reflectionFunction, ...$values)
    {
        try {
            return $reflectionFunction->invoke(...$values);
        } catch (Error $e) {
            $message = $e->getMessage();
            if (str_contains($message, 'must not be accessed before initialization')) {
                return null;
            }
            throw $e;
        }
    }
}
