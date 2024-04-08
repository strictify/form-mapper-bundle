<?php

declare(strict_types=1);

namespace Strictify\FormMapper\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Strictify\FormMapper\Service\Comparator\DataComparatorInterface;

class StrictifyFormMapperExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.xml');

        $container->registerForAutoconfiguration(DataComparatorInterface::class)
            ->addTag('strictify_form_mapper.comparator');
    }
}
