<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Application;

use Strictify\FormMapper\Extension\FactoryExtension;
use Strictify\FormMapper\Extension\MapperExtension;
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
        return [
            new FactoryExtension(),
            new MapperExtension(),
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
