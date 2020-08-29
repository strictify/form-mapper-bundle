<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Accessor;

use Closure;
use PHPUnit\Framework\TestCase;
use Strictify\FormMapper\Accessor\Accessor;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Strictify\FormMapper\Tests\Fixture\Factory;

/**
 * @covers \Strictify\FormMapper\Accessor\Accessor::write
 */
class WriteTest extends TestCase
{
    private User $user;
    private Accessor $accessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User('Bruce', 'Willis');
        $this->accessor = Factory::createAccessor();
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

        $this->callOnMock($user, 'John');
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

        $this->callOnMock($user, 'Bruce');
    }

    /**
     * When there is no param in $writer, just ignore the call.
     */
    public function testMissingParams(): void
    {
        $user = $this->user;
        [$getter] = $this->getterAndUpdater();
        $updater = function () {
            $this->fail('This should have not been called.');
        };
        $this->accessor->write($getter, $updater, $user, 'John');
        self::assertEquals('Bruce', $user->getFirstName());
    }

    /**
     * When typehint is missing, still make a call but create E_USER_WARNING.
     */
    public function testMissingTypehint(): void
    {
        $user = $this->user;
        [$getter] = $this->getterAndUpdater();
        $updater = /** @param string $firstName */ fn ($firstName, User $user) => $user->changeFirstName($firstName);
        $this->accessor->write($getter, $updater, $user, 'John');
        self::assertEquals('John', $user->getFirstName());
    }

    private function callOnMock($mock, $submittedData): void
    {
        [$getter, $updater] = $this->getterAndUpdater();
        $this->accessor->write($getter, $updater, $mock, $submittedData);
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
