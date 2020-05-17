<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests;

use Strictify\FormMapper\Extension\FactoryExtension;
use Strictify\FormMapper\Extension\MapperExtension;
use Strictify\FormMapper\Tests\Fixture\Factory;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

abstract class AbstractTypeTestCase extends TypeTestCase
{
    /**
     * Extensions for bundle itself.
     */
    protected function getTypeExtensions(): array
    {
        $comparator = Factory::createComparator();

        return [
            new FactoryExtension(),
            new MapperExtension($comparator),
        ];
    }

    /**
     * These are needed from Core.
     *
     * @return ValidatorExtension[]
     */
    protected function getExtensions(): array
    {
        $validator = Validation::createValidator();

        return [
            new ValidatorExtension($validator),
        ];
    }
}
