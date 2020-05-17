<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Service;

use DateTime;
use PHPUnit\Framework\TestCase;
use Strictify\FormMapper\Service\Comparator;
use Strictify\FormMapper\Tests\Fixture\Factory;

class ComparatorTest extends TestCase
{
    private Comparator $comparator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->comparator = Factory::createComparator();
    }

    public function testEqualDates(): void
    {
        $first = new DateTime('2010-12-31');
        $second = new DateTime('2010-12-31');
        self::assertTrue($this->comparator->isEqual($first, $second));
    }
}
