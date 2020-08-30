<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Accessor;

use ArrayObject;
use Closure;
use Strictify\FormMapper\Accessor\Accessor;
use Strictify\FormMapper\Tests\AbstractTypeTestCase;
use Strictify\FormMapper\Tests\Fixture\Entity\Movie;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Strictify\FormMapper\Tests\Fixture\Factory;

/**
 * @covers \Strictify\FormMapper\Accessor\Accessor::writeCollection()
 */
class WriteCollectionTest extends AbstractTypeTestCase
{
    private Accessor $accessor;
    private Movie $dieHard;
    private Movie $pulpFiction;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessor = Factory::createAccessor();

        $this->dieHard = new Movie('Die hard');
        $this->pulpFiction = new Movie('Pulp fiction');
        $this->user = new User('Bruce', 'Willis', [$this->dieHard, $this->pulpFiction]);
    }

    public function testSimple(): void
    {
        $user = $this->user;
        $this->callOnMock($user, [$this->dieHard, $this->pulpFiction]);
        $favoriteMovie = $user->getFavoriteMovies();
        self::assertEquals([$this->dieHard, $this->pulpFiction], $favoriteMovie);
    }

    public function testRemovedOne(): void
    {
        $user = $this->user;
        $this->callOnMock($user, new ArrayObject([$this->dieHard]));
        $favoriteMovie = $user->getFavoriteMovies();
        self::assertEquals([$this->dieHard], $favoriteMovie);
    }

    /**
     * When same values are submitted, adder and remover must not be called.
     */
    public function testAdderAndRemoverWereNotCalled(): void
    {
        $mock = $this->getUserMock();
        $mock->expects($this->never())
            ->method('addFavoriteMovie');
        $mock->expects($this->never())
            ->method('removeFavoriteMovie');

        $this->callOnMock($mock, [$this->dieHard, $this->pulpFiction]);
    }

    public function testSupportForTraversable(): void
    {
        $looper = new Movie('Looper');
        $mock = $this->getUserMock();
        $mock->expects($this->once())
            ->method('addFavoriteMovie')
            ->with($looper);

        $mock->expects($this->never())
            ->method('removeFavoriteMovie');

        $this->callOnMock($mock, new ArrayObject([$this->dieHard, $this->pulpFiction, $looper]));
    }

    public function testOnlyAdderCalled(): void
    {
        $mock = $this->getUserMock();
        $mock->expects($this->once())
            ->method('addFavoriteMovie');
        $mock->expects($this->never())
            ->method('removeFavoriteMovie');

        $this->callOnMock($mock, [$this->dieHard, $this->pulpFiction, new Movie('Looper')]);
    }

    public function testOnlyRemoverCalled(): void
    {
        $mock = $this->getUserMock();
        $mock->expects($this->never())
            ->method('addFavoriteMovie');
        $mock->expects($this->once())
            ->method('removeFavoriteMovie')
            ->with($this->dieHard)
        ;

        $this->callOnMock($mock, [1 => $this->pulpFiction]);
    }

    private function getUserMock()
    {
        $mock = $this->createMock(User::class);
        $mock->expects($this->atMost(2))
            ->method('getFavoriteMovies')
            ->willReturn(new ArrayObject([$this->dieHard, $this->pulpFiction]));

        return $mock;
    }

    private function callOnMock($mock, iterable $movies): void
    {
        [$compare, $getter, $adder, $remover] = $this->getCallbacks();
        $this->accessor->writeCollection($compare, $getter, $adder, $remover, $mock, $movies);
    }

    /**
     * @return Closure[]
     */
    private function getCallbacks(): array
    {
        return [
            fn($default, $submitted) => $default === $submitted,
            fn (User $user) => $user->getFavoriteMovies(),
            fn (Movie $movie, User $user) => $user->addFavoriteMovie($movie),
            fn (Movie $movie, User $user) => $user->removeFavoriteMovie($movie),
        ];
    }
}
