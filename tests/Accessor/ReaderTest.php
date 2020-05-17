<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Accessor;

use PHPUnit\Framework\TestCase;
use Strictify\FormMapper\Accessor\Accessor;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Strictify\FormMapper\Tests\Fixture\Factory;

/**
 * @covers \Strictify\FormMapper\Accessor\Accessor::read
 */
class ReaderTest extends TestCase
{
    private User $user;
    private Accessor $accessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User('Bruce', 'Willis');
        $this->accessor = Factory::createAccessor();
    }

    /**
     * Data exists; run callable.
     */
    public function testSimple(): void
    {
        $user = $this->user;
        $getter = fn (User $user) => 'abc';
        $value = $this->accessor->read($getter, $user, false);
        self::assertEquals('abc', $value);
    }

    /**
     * No data; return default of `null` for non-collection field.
     */
    public function testNullDataAndTypehintedGetter(): void
    {
        $getter = fn (User $user) => 'abc';
        $value = $this->accessor->read($getter, null, false);
        self::assertNull($value);
    }

    /**
     * If missing first param, make a call. Useful when $builder->getData() is used.
     */
    public function testDefaultWhenMissingFirstParam(): void
    {
        $getter = fn () => 'abc';
        $value = $this->accessor->read($getter, null, false);
        self::assertEquals('abc', $value);
    }

    /**
     * Useful for setting default value when data still doesn't exist.
     */
    public function testNullableTypehint(): void
    {
        $getter = fn (?User $user) => 'abc';
        $value = $this->accessor->read($getter, null, false);
        self::assertEquals('abc', $value);
    }

    public function testSimpleCollection(): void
    {
        $getter = fn (User $user) => 'abc';
        $value = $this->accessor->read($getter, null, true);
        self::assertIsArray($value);
    }
}
