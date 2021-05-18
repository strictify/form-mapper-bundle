<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Extension\Mapper;

use Strictify\FormMapper\Tests\AbstractTypeTestCase;
use Strictify\FormMapper\Tests\Fixture\Form\UserTestType;

class NotNullConstraintsTest extends AbstractTypeTestCase
{
    /**
     * Bundle will add default NotNull constraint when ``update_value($value)`` does NOT accept nullable.
     *
     * @see UserTestType::buildForm() ; firstName doesn't have constraints, but lastName does.
     */
    public function testDefaultNotNullConstraint(): void
    {
        [$user, $form] = $this->createUserData();

        $form->submit(['firstName' => null, 'lastName' => 'Smith']);
        self::assertFalse($form->isValid());
        // updater did not trigger
        self::assertEquals('Bruce', $user->getFirstName());

        $errors = (string)$form->getErrors(true, true);
        // this is default message from NotNull constraint
        self::assertStringContainsString('This value should not be null.', $errors);
    }

    /**
     * If user added custom NotNull constraint, that one will be used instead of default.
     */
    public function testCustomNotNullConstraint(): void
    {
        [$user, $form] = $this->createUserData();

        $form->submit(['firstName' => 'John', 'lastName' => null]);
        // updater did not trigger
        self::assertEquals('Willis', $user->getLastName());

        $errors = (string)$form->getErrors(true, true);
        // this is message from custom NotNull constraint added
        self::assertStringContainsString('Custom not null message.', $errors);
    }
}
