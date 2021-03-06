<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Fixture\Form;

use Strictify\FormMapper\Tests\Fixture\Entity\Movie;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<Movie>
 */
class MovieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'get_value' => function (Movie $movie) {
                return $movie->getName();
            },
            'update_value' => function (string $name, Movie $movie): void {
                $movie->rename($name);
            },
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('factory', fn(string $name) => new Movie($name));
    }
}
