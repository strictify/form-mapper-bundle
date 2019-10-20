<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\TestExtension;

use Strictify\FormMapper\Tests\Application\Factory\Factory;
use Symfony\Component\Form\Test\TypeTestCase;

abstract class AbstractTypeTestCase extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [
            Factory::getFormExtension(),
        ];
    }
}
