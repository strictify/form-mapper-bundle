<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Extension;

use Closure;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class RemoveEntryExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        yield FormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            '_collection_remove_entry_callback' => null,
            'remove_entry' => fn() => null,
        ]);
        $resolver->setAllowedTypes('remove_entry', [Closure::class]);

        $resolver->setNormalizer('_collection_remove_entry_callback', function (Options $options) {


        });
    }
}
