<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Extension\Mapper;

use stdClass;
use Strictify\FormMapper\Extension\MapperExtension;
use Strictify\FormMapper\Tests\AbstractTypeTestCase;
use Strictify\FormMapper\Tests\Fixture\Entity\User;
use Strictify\FormMapper\Tests\Fixture\Form\UserTestType;

/**
 * Test if constraints are added per field level, based on typehint of first param in `get_value`.
 *
 * @see MapperExtension::normalizeConstraints()
 */
class UpdateConstraintsTest extends AbstractTypeTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = new User('Bruce', 'Willis');
    }

    public function testNotNullApplied(): void
    {
        $user = $this->user;
        $form = $this->factory->create(UserTestType::class, $user);
        $form->submit(['firstName' => 'John', 'lastName' => null]);

        self::assertFalse($form->isValid());
        $lastName = $form->get('lastName');
        self::assertFalse($lastName->isValid());
        $errorAsString = $lastName->getErrors()->__toString();
        self::assertStringContainsString('This value should not be null.', $errorAsString);
    }

    public function testTypeApplied(): void
    {
        $user = $this->user;
        $form = $this->factory->create(UserTestType::class, $user);
        $form->submit(['firstName' => 'John', 'lastName' => new stdClass()]);

        self::assertFalse($form->isValid());
        $lastName = $form->get('lastName');
        $errorAsString = $lastName->getErrors()->__toString();
        self::assertFalse($lastName->isValid());
        self::assertStringContainsString('This value should be of type', $errorAsString);
    }
}
