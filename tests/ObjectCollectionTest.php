<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests;

use Strictify\FormMapper\Accessor\AccessorBuilder;
use Strictify\FormMapper\Tests\Application\Entity\Movie;
use Strictify\FormMapper\Tests\Application\Entity\User;
use PHPUnit\Framework\TestCase;
use Strictify\FormMapper\Tests\Application\Repository\TestRepository;

class ObjectCollectionTest extends TestCase
{
    private $accessor;
    private $repository;
    private $user;
    private $moviesConfig;

    protected function setUp(): void
    {
        $this->accessor = (new AccessorBuilder())->getAccessor();
        $this->repository = new TestRepository();
        $this->user = $this->repository->getArnold();

        $this->moviesConfig = [
            'get_value' => static function (User $user) {
                return $user->getMovies();
            },
            'add_value' => static function (Movie $movie, User $user): void {
                $user->addMovie($movie);
            },
            'remove_value' => static function (Movie $movie, User $user): void {
                $user->removeMovie($movie);
            },
        ];
    }

    public function testCollection(): void
    {
        $user = $this->user;

        $predator = $this->repository->getPredatorMovie();
        $submitted = [1 => $predator];
        $this->accessor->update($user, $submitted, $this->moviesConfig);
        $this->assertSame($submitted, $user->getMovies());

        $eraser = new Movie('Eraser');
        $submitted = [2 => $eraser];
        $this->accessor->update($user, $submitted, $this->moviesConfig);
        $this->assertSame($submitted, $user->getMovies());
    }
}
