<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\TestExtension;

use Strictify\FormMapper\Tests\Application\Entity\Movie;
use Strictify\FormMapper\Tests\Application\Factory\Factory;
use Strictify\FormMapper\Tests\Application\Form\MovieType;

class FormExtensionTest extends AbstractTypeTestCase
{
    /**
     * It is not allowed to submit `null` for name. If that happens, add validation error.
     */
    public function testNullValueWhereNotAllowed(): void
    {
        // we don't need factory error here, just for the `name` field
        $form = $this->factory->create(MovieType::class, null, [
            'factory_error_message' => null,
        ]);
        $form->submit(['name' => null]);
        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->getErrors(true));
    }

    public function testValidUpdater(): void
    {
        $terminator = Factory::getRepository()->getTerminatorMovie();
        $form = $this->factory->create(MovieType::class, $terminator);
        $form->submit(['name' => 'Eraser']);
        /** @var Movie $movie */
        $movie = $form->getData();
        $this->assertInstanceOf(Movie::class, $movie);
        $this->assertEquals('Eraser', $movie->getName());
    }
}
