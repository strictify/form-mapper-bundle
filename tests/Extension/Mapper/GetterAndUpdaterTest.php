<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Extension\Mapper;

use Strictify\FormMapper\Tests\AbstractTypeTestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class GetterAndUpdaterTest extends AbstractTypeTestCase
{
    public function testSimple(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessageMatches('/"update_value"/');

        $this->factory->createBuilder()
            ->add('name', null, [
                'get_value' => fn () => 'John',
            ])
            ->getForm();
    }

    public function testMissingAdder(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessageMatches('/"add_value"/');

        $this->factory->createBuilder()
            ->add('list', CollectionType::class, [
                'get_value' => fn () => [],
                'remove_value' => fn () => [],
            ])
            ->getForm();
    }

    public function testMissingRemover(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessageMatches('/"remove_value"/');

        $this->factory->createBuilder()
            ->add('list', CollectionType::class, [
                'get_value' => fn () => [],
                'add_value' => fn () => null,
            ])
            ->getForm();
    }
}
