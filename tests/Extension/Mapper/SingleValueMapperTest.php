<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Extension\Mapper;

use Symfony\Component\Form\FormInterface;
use Strictify\FormMapper\Tests\Fixture\Factory;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Strictify\FormMapper\Tests\AbstractTypeTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class SingleValueMapperTest extends AbstractTypeTestCase
{
    /**
     * Testing valid setup of ``get_value`` and ``update_value``
     */
    public function testSimple(): void
    {
        $user = Factory::createUser();
        $form = $this->createUserForm($user);

        $form->submit(['firstName' => 'John', 'lastName' => 'Smith']);
        self::assertEquals('John', $user->getFirstName());
        self::assertEquals('Smith', $user->getLastName());
        self::assertTrue($form->isValid());
    }

    /**
     * ``update_value`` doesn't need to have second parameter.
     *
     * Other rules for first parameter still apply.
     */
    public function testUpdateValueWithoutSecondParameter(): void
    {
        $user = Factory::createUser();
        $form = $this->createTestForm($user);
        $form->submit(['firstName' => 'John']);
        self::assertEquals('John', $user->getFirstName());
    }

    /**
     * @return FormInterface<User>
     */
    private function createTestForm(User $user)
    {
        $builder = $this->factory->createBuilder(FormType::class, $user);
        $builder->add('firstName', TextType::class, [
            'get_value'    => fn(User $user) => $user->getFirstName(),
            'update_value' => fn(string $firstName) => $user->changeFirstName($firstName),
        ]);

        return $builder->getForm();
    }
}
