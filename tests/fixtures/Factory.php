<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Fixture;

use Strictify\FormMapper\Service\Comparator;
use Strictify\FormMapper\Tests\Fixture\Entity\User;

class Factory
{
    public static function createUser(): User
    {
        return new User('Bruce', 'Willis');
    }

    public static function createComparator(): Comparator
    {
        return new Comparator([new Comparator\DateTimeDataComparator()]);
    }

}
