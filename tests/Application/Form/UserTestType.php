<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Application\Form;

use Strictify\FormMapper\Tests\Application\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserTestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('firstName', TextType::class, [
            'get_value' => fn(User $user) => $user->getFirstName(),
            'update_value' => fn(string $firstName, User $user) => $user->setFirstName($firstName),
        ]);

        $builder->add('lastName', TextType::class, [
            'get_value' => fn(User $user) => $user->getLastName(),
            'update_value' => fn(string $lastName, User $user) => $user->setLastName($lastName),
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('factory', function (string $firstName, string $lastName) {
            return new User($firstName, $lastName);
        });
        // this one is needed for default mapper
        $resolver->setDefault('data_class', User::class);
    }
}
