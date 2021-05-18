<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Fixture\Form;

use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<User>
 */
class UserTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('firstName', TextType::class, [
            'get_value'    => fn(User $user) => $user->getFirstName(),
            'update_value' => fn(string $firstName, User $user) => $user->changeFirstName($firstName),
        ]);

        $builder->add('lastName', TextType::class, [
            'get_value'    => fn(User $user) => $user->getLastName(),
            'update_value' => fn(string $lastName, User $user) => $user->changeLastName($lastName),
            'constraints'  => [
                new NotNull(['message' => 'Custom not null message.']),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('factory', fn(string $firstName, string $lastName) => new User($firstName, $lastName));
    }
}
