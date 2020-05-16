<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests;

use Strictify\FormMapper\Extension\FactoryExtension;
use Strictify\FormMapper\Extension\FieldAccessExtension;
use Symfony\Component\Form\Test\TypeTestCase;

abstract class AbstractTypeTestCase extends TypeTestCase
{
    protected function getTypeExtensions(): array
    {
        return [
            new FactoryExtension(),
            new FieldAccessExtension(),
        ];
    }
}
