<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\TestExtension;

use Strictify\FormMapper\Exception\FactoryException;
use Strictify\FormMapper\Tests\Application\Entity\Movie;
use Strictify\FormMapper\Tests\Application\Form\MovieType;

class FormFactoryTest extends AbstractTypeTestCase
{
    public function testFactory(): void
    {
        $form = $this->factory->create(MovieType::class);
        $form->submit(['name' => 'Eraser']);
        /** @var Movie $movie */
        $movie = $form->getData();
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertEquals('Eraser', $movie->getName());
    }

    public function testWrongFactorySignature(): void
    {
        $this->expectException(FactoryException::class);
        $form = $this->factory->create(MovieType::class, null, [
            'factory' => function (string $wrongName) {
                return new Movie($wrongName);
            },
        ]);
        $form->submit(['name' => 'Eraser']);
    }
}
