<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Extension\Factory;

use stdClass;
use TypeError;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Strictify\FormMapper\Tests\AbstractTypeTestCase;

class FactoryTest extends AbstractTypeTestCase
{
    public function testUserIsCreatedWithValidData(): void
    {
        $form = $this->createUserForm();
        $form->submit(['firstName' => 'Bruce', 'lastName' => 'Willis']);
        self::assertTrue($form->isValid());
        /** @var User $user */
        $user = $form->getData();
        self::assertEquals('Bruce', $user->getFirstName());
        self::assertEquals('Willis', $user->getLastName());
    }

    /**
     * Factory requires `string $lastName` but null is submitted; form must become invalid.
     */
    public function testInvalidLastName(): void
    {
        $form = $this->createUserForm();
        $form->submit(['firstName' => 'Bruce', 'lastName' => null]);
        self::assertFalse($form->isValid());
        self::assertNull($form->getData());
    }

    /**
     * Factory requires `string $lastName` but it is not submitted; form must become invalid.
     */
    public function testMissingField(): void
    {
        $form = $this->createUserForm();
        $form->submit(['firstName' => 'Bruce']);
        self::assertFalse($form->isValid());
    }

    /**
     * Assert that incorrect type submitted will trigger TypeError exception, instead of converting it to validation error.
     *
     * Users have to use static analysis.
     */
    public function testTypeErrorException(): void
    {
        $this->expectException(TypeError::class);
        $form = $this->createUserForm();
        $form->submit(['firstName' => 'Bruce', 'lastName' => new stdClass()]);
    }
}
