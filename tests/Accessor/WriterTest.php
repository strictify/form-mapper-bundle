<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Accessor;

use Closure;
use PHPUnit\Framework\TestCase;
use Strictify\FormMapper\Accessor\Accessor;
use Strictify\FormMapper\Tests\Application\Entity\User;

class WriterTest extends TestCase
{
    private User $user;
    private Accessor $accessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User('Bruce', 'Willis');
        $this->accessor = new Accessor();
    }

    public function testSimple(): void
    {
        $user = $this->user;
        [$getter, $updater] = $this->getterAndUpdater();
        $this->accessor->write($getter, $updater, $user, 'John');
        self::assertEquals('John', $user->getFirstName());
    }

    public function testUpdaterCalledOnce(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getFirstName')
            ->willReturn('Bruce');

        $user->expects($this->once())
            ->method('changeFirstName');

        [$getter, $updater] = $this->getterAndUpdater();
        $this->accessor->write($getter, $updater, $user, 'John');
    }

    /**
     * If the original and submitted values are the same, do not call `update_value`.
     */
    public function testUpdaterNotCalledForSameValue(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())
            ->method('getFirstName')
            ->willReturn('Bruce');

        $user->expects($this->never())
            ->method('changeFirstName');

        [$getter, $updater] = $this->getterAndUpdater();
        $this->accessor->write($getter, $updater, $user, 'Bruce');
    }

    /**
     * @return Closure[]
     */
    private function getterAndUpdater(): array
    {
        return [
            fn (User $user) => $user->getFirstName(),
            fn (string $firstName, User $user) => $user->changeFirstName($firstName),
        ];
    }
}
