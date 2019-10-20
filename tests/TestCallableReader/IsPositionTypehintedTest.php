<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\TestCallableReader;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Strictify\FormMapper\Tests\Application\Factory\Factory;

class IsPositionTypehintedTest extends TestCase
{
    private $reader;

    protected function setUp(): void
    {
        $this->reader = Factory::getCallableReader();
    }

    public function testClosureTypehintProvided(): void
    {
        $closure = function (string $name): void {};
        $this->assertTrue($this->reader->isPositionTypehinted($closure, 0));
    }

    public function testClosureTypehintNotProvided(): void
    {
        $closure = function ($name): void {};
        $this->assertFalse($this->reader->isPositionTypehinted($closure, 0));
    }

    /**
     * @see typehintProvided
     */
    public function testArrayCallableTypeHintProvided(): void
    {
        $closure = [$this, 'typehintProvided'];
        $this->assertTrue($this->reader->isPositionTypehinted($closure, 0));
    }

    /**
     * @see typehintNotProvided
     */
    public function testArrayCallableTypeHintNotProvided(): void
    {
        $closure = [$this, 'typehintNotProvided'];
        $this->assertFalse($this->reader->isPositionTypehinted($closure, 0));
    }

    public function testUnsupportedCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->reader->isPositionTypehinted('sleep', 0);
    }

    public function typehintProvided(string $name): void
    {
    }

    public function typehintNotProvided($name): void
    {
    }
}
