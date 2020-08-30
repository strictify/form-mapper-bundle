<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Service\Comparator;

use DateTime;
use PHPUnit\Framework\TestCase;
use Strictify\FormMapper\Service\Comparator\DateTimeDataComparator;

class DateTimeComparatorTest extends TestCase
{
    private DateTimeDataComparator $comparator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->comparator = new DateTimeDataComparator();
    }

    public function testEqual(): void
    {
        $first = new DateTime('2010-12-31');
        $second = new DateTime('2010-12-31');
        self::assertTrue($this->comparator->isEqual($first, $second));
    }

    public function testSecondIsNull(): void
    {
        $first = new DateTime('2010-12-31');
        $second = null;
        self::assertFalse($this->comparator->isEqual($first, $second));
    }

    public function testFirstIsNull(): void
    {
        $first = null;
        $second = new DateTime('2010-12-31');
        self::assertFalse($this->comparator->isEqual($first, $second));
    }

    public function testNotDateTime(): void
    {
        $first = '42';
        $second = '42';
        self::assertFalse($this->comparator->isEqual($first, $second));
    }
}
