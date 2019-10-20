<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Application\Factory;

use Strictify\FormMapper\Accessor\Accessor;
use Strictify\FormMapper\Accessor\AccessorInterface;
use Strictify\FormMapper\Accessor\Comparator\DateTimeComparator;

class AccessorBuilder
{
    public function getAccessor(): AccessorInterface
    {
        $dateTimeComparator = new DateTimeComparator();

        return new Accessor([$dateTimeComparator]);
    }
}
