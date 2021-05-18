<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validation;
use Strictify\FormMapper\Service\Comparator;
use Symfony\Component\Form\Test\TypeTestCase;
use Strictify\FormMapper\Tests\Fixture\Factory;
use Strictify\FormMapper\Extension\MapperExtension;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Strictify\FormMapper\Extension\FactoryExtension;
use Strictify\FormMapper\Tests\Fixture\Form\UserTestType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;

abstract class AbstractTypeTestCase extends TypeTestCase
{
    /**
     * Extensions for bundle itself.
     */
    protected function getTypeExtensions(): array
    {
        $comparator = new Comparator([new Comparator\DateTimeDataComparator()]);

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

    /**
     * @return array{0: User, 1: FormInterface<User>}
     */
    protected function createUserData()
    {
        $user = Factory::createUser();
        $form = $this->createUserForm($user);

        return [$user, $form];
    }

    /**
     * @return FormInterface<User>
     */
    protected function createUserForm(?User $user = null): FormInterface
    {
        return $this->factory->createBuilder(UserTestType::class, $user)->getForm();
    }
}
