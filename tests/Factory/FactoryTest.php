<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Factory;

use Strictify\FormMapper\Tests\AbstractTypeTestCase;
use Strictify\FormMapper\Tests\Application\Entity\User;
use Strictify\FormMapper\Tests\Application\Form\UserTestType;

class FactoryTest extends AbstractTypeTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User('John', 'Wick');
    }

    /**
     * Test fixture, just in case. It will be easier to debug tests if User was changed later.
     */
    public function testGetter(): void
    {
        $user = $this->user;
        self::assertEquals('John', $user->getFirstName());
    }

    public function testUserIsCreated(): void
    {
        $form = $this->factory->create(UserTestType::class, null);
        $form->submit(['firstName' => 'Bruce', 'lastName' => 'Willis']);
        self::assertTrue($form->isValid());
        /** @var User $user */
        $user = $form->getData();
        self::assertEquals('Bruce', $user->getFirstName());
    }

    public function testMissingField(): void
    {
        $form = $this->factory->create(UserTestType::class, null);
        $form->submit(['firstName' => 'Bruce']);
        self::assertFalse($form->isValid());
    }
}
