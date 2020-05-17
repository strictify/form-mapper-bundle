<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Fixture;

use Strictify\FormMapper\Accessor\Accessor;
use Strictify\FormMapper\Service\Comparator;

class Factory
{
    public static function createComparator(): Comparator
    {
        return new Comparator([new Comparator\DateTimeComparator()]);
    }

    public static function createAccessor(): Accessor
    {
        return new Accessor(self::createComparator());
    }
}
