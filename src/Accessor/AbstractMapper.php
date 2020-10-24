<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use ReflectionFunction;
use InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Strictify\FormMapper\Service\Comparator;

abstract class AbstractMapper implements MapperInterface
{
    protected Comparator $comparator;

    public function __construct(Comparator $comparator)
    {
        $this->comparator = $comparator;
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
        // we have data; make a call to getter
        if (null !== $data) {
            return $getter($data);
        }

        $reflection = new ReflectionFunction($getter);
        $params = $reflection->getParameters();
        $firstParam = $params[0] ?? null;
        // if no first param and no data, still make a call to getter; useful with `$builder->getData()` and arrow functions.
        if (null === $firstParam) {
            return $getter();
        }

        // still no data (example: factory failure) but nullable is not allowed; return default as well
        if ($firstParam->allowsNull()) {
            return $getter(null);
        }
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
}
