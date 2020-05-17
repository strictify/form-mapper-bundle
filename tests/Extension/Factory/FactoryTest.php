<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Extension\Factory;

use Closure;
use Strictify\FormMapper\Tests\AbstractTypeTestCase;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;

class FactoryTest extends AbstractTypeTestCase
{
    public function testUserIsCreatedWithValidData(): void
    {
        $form = $this->createUserForm();
        $form->submit(['firstName' => 'Bruce', 'lastName' => 'Willis']);
        self::assertTrue($form->isValid());
        /** @var User $user */
        $user = $form->getData();
        self::assertEquals('Bruce', $user->getFirstName());
        self::assertEquals('Willis', $user->getLastName());
    }

    /**
     * Factory requires `string $lastName` but null is submitted; form must become invalid.
     */
    public function testInvalidLastName(): void
    {
        $form = $this->createUserForm();
        $form->submit(['firstName' => 'Bruce', 'lastName' => null]);
        self::assertFalse($form->isValid());
    }

    /**
     * Factory requires `string $lastName` but it is not submitted; form must become invalid.
     */
    public function testMissingField(): void
    {
        $form = $this->createUserForm();
        $form->submit(['firstName' => 'Bruce']);
        self::assertFalse($form->isValid());
    }

    public function testFormInterfaceIsInjected(): void
    {
        $factory = fn (FormInterface $form, string $lastName) => new User($form->get('firstName')->getData(), $lastName);
        $form = $this->createUserForm($factory);
        $form->submit(['firstName' => 'Bruce', 'lastName' => 'Wick']);
        self::assertTrue($form->isValid());
    }

    /**
     * This test if just for factory, accessors are irrelevant.
     */
    private function createUserForm(?Closure $factory = null): FormInterface
    {
        if (!$factory) {
            $factory = fn (string $firstName, string $lastName) => new User($firstName, $lastName);
        }
        $builder = $this->factory->createBuilder(FormType::class, null, ['factory' => $factory]);
        $builder->add('firstName', null, [
            'mapped' => false,
        ]);

        $builder->add('lastName', null, [
            'mapped' => false,
        ]);

        return $builder->getForm();
    }
}
