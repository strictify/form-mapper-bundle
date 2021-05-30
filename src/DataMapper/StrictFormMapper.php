<?php

declare(strict_types=1);

namespace Strictify\FormMapper\DataMapper;

use Closure;
use Strictify\FormMapper\Types;
use Strictify\FormMapper\Service\Comparator;
use Symfony\Component\Form\DataMapperInterface;
use Strictify\FormMapper\Accessor\MapperInterface;
use Strictify\FormMapper\Accessor\CollectionMapper;
use Strictify\FormMapper\Accessor\SingleValueMapper;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;

/**
 * @psalm-import-type O from Types
 *
 * @see Closure
 * @see Types
 */
class StrictFormMapper implements DataMapperInterface
{
    private DataMapperInterface $defaultMapper;
    private SingleValueMapper $singleValueMapper;
    private CollectionMapper $collectionMapper;

    /** @var array<string, MapperInterface> */
    private array $cachedMappers = [];

    public function __construct(?DataMapperInterface $defaultMapper, Comparator $comparator)
    {
        $this->defaultMapper = $defaultMapper ?: new DataMapper();
        $this->singleValueMapper = new SingleValueMapper($comparator);
        $this->collectionMapper = new CollectionMapper($comparator);
    }

    public function mapDataToForms($viewData, $forms): void
    {
        $unmappedForms = [];

        foreach ($forms as $name => $form) {
            /** @psalm-var O $options */
            $options = $form->getConfig()->getOptions();
            $getter = $options['get_value'];

            if (!$getter) {
                $unmappedForms[] = $form;
                continue;
            }
            $accessor = $this->getAccessor($options, $name);

            /** @psalm-var mixed $value */
            $value = $accessor->read($options, $viewData, $form);
            $form->setData($value);
        }

        $this->defaultMapper->mapDataToForms($viewData, $unmappedForms);
    }

    public function mapFormsToData($forms, &$viewData): void
    {
        $unmappedForms = [];

        foreach ($forms as $name => $form) {
            $config = $form->getConfig();
            /** @psalm-var O $options */
            $options = $config->getOptions();
            $getter = $options['get_value'];

            if ($getter && $config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && !$form->isDisabled()) {
                $accessor = $this->getAccessor($options, $name);
                $accessor->update($options, $viewData, $form);
            } else {
                $unmappedForms[] = $form;
            }
        }

        $this->defaultMapper->mapFormsToData($unmappedForms, $viewData);
    }

    /**
     * @psalm-param O $options
     */
    private function getAccessor(array $options, string $name): MapperInterface
    {
        return $this->cachedMappers[$name] ??= $this->doGetAccessor($options);
    }

    /**
     * @psalm-param O $options
     */
    private function doGetAccessor(array $options): MapperInterface
    {
        $isCollection = $this->isCollection($options);

        return $isCollection ? $this->collectionMapper : $this->singleValueMapper;
    }

    /**
     * @psalm-param O $options
     */
    private function isCollection(array $options): bool
    {
        if (isset($options['multiple']) && true === $options['multiple']) {
            return true;
        }
        if (isset($options['entry_type'])) {
            return true;
        }

        return false;
    }
}
