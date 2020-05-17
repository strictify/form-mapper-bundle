<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests;

use Strictify\FormMapper\Tests\Application\AbstractTypeTestCase;
use Strictify\FormMapper\Tests\Application\Entity\User;
use Strictify\FormMapper\Tests\Application\Form\UserTestType;

class FieldMapperTest extends AbstractTypeTestCase
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User('Bruce', 'Willis');
    }

    public function testNameChanged(): void
    {
        $user = $this->user;
        $form = $this->factory->create(UserTestType::class, $user);
        $form->submit(['firstName' => 'John', 'lastName' => 'Wick']);

        self::assertTrue($form->isValid());
        self::assertEquals('John', $user->getFirstName());
        self::assertEquals('Wick', $user->getLastName());
    }
}
