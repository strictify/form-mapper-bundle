<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\TestExtension;

use Strictify\FormMapper\Tests\Application\Entity\Movie;
use Strictify\FormMapper\Tests\Application\Form\MovieType;

class FormExtensionTest extends AbstractTypeTestCase
{
    public function testFactory(): void
    {
        $form = $this->factory->create(MovieType::class);
        $form->submit(['name' => 'Eraser']);
        $movie = $form->getData();
        $this->assertInstanceOf(Movie::class, $movie);
    }
}
