<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Strictify\FormMapper\Service\Comparator;
use Strictify\FormMapper\Extension\MapperExtension;
use Strictify\FormMapper\Extension\FactoryExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Strictify\FormMapper\Extension\RemoveEntryExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Strictify\FormMapper\Service\Comparator\DateTimeDataComparator;
use Strictify\FormMapper\Service\Comparator\DataComparatorInterface;

return static function (ContainerConfigurator $container, ContainerBuilder $builder) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set('strictify_form_mapper.mapper_extension', MapperExtension::class)
        ->args([service('strictify_form_mapper.comparator')])
        ->tag('form.type_extension', ['priority' => 0, 'extended-type' => FormType::class, 'extended_type' => FormType::class]);

    $services->set('strictify_form_mapper.factory_extension', FactoryExtension::class)
        ->tag('form.type_extension', ['priority' => 1, 'extended-type' => FormType::class, 'extended_type' => FormType::class]);

    $services->set('strictify_form_mapper.collection_extension', RemoveEntryExtension::class)
        ->tag('form.type_extension', ['priority' => 1, 'extended-type' => FormType::class, 'extended_type' => FormType::class]);

    $services->set('strictify_form_mapper.comparator', Comparator::class)
        ->args([tagged_iterator('strictify_form_mapper.comparator')]);

    $services->set('strictify_form_mapper.comparator.date_type', DateTimeDataComparator::class)
        ->tag('strictify_form_mapper.comparator');

    $builder->registerForAutoconfiguration(DataComparatorInterface::class)
        ->addTag('strictify_form_mapper.comparator');
};
