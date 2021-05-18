<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Extension\Mapper;

use Symfony\Component\Form\FormInterface;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Strictify\FormMapper\Tests\AbstractTypeTestCase;
use Strictify\FormMapper\Tests\Fixture\Entity\Movie;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class CollectionTest extends AbstractTypeTestCase
{
    private Movie $dieHard;
    private Movie $pulpFiction;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dieHard = new Movie('Die Hard');
        $this->pulpFiction = new Movie('Pulp fiction');
        $this->user = new User('Bruce', 'Willis', [$this->dieHard, $this->pulpFiction]);
    }

    public function testSimple(): void
    {
        $user = $this->user;
        $form = $this->getTestForm($user);
        $form->submit(['favoriteMovies' => [$this->dieHard, $this->pulpFiction]]);
        self::assertTrue($form->isValid());
    }

    private function getTestForm(?User $user): FormInterface
    {
        return $this->factory->createBuilder(FormType::class, $user)
            ->add('favoriteMovies', CollectionType::class, [
                'allow_add' => true,
                'allow_delete' => true,
                'get_value' => fn (User $user) => $user->getFavoriteMovies(),
                'add_value' => fn (Movie $movie, User $user) => $user->addFavoriteMovie($movie),
                'remove_value' => fn (Movie $movie, User $user) => $user->removeFavoriteMovie($movie),
            ])
            ->getForm();
    }
}
