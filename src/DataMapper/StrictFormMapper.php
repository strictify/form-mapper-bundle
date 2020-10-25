<?php

declare(strict_types=1);

namespace Strictify\FormMapper\DataMapper;

use Closure;
use Strictify\FormMapper\Types;
use Strictify\FormMapper\VO\SubmittedData;
use Strictify\FormMapper\Service\Comparator;
use Symfony\Component\Form\DataMapperInterface;
use Strictify\FormMapper\Accessor\MapperInterface;
use Strictify\FormMapper\Accessor\CollectionMapper;
use Strictify\FormMapper\Accessor\SingleValueMapper;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;

/**
 * @see SubmittedData
 *
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

    public function __construct(?DataMapperInterface $defaultMapper, Comparator $comparator)
    {
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->defaultMapper = $defaultMapper ?: new PropertyPathMapper();
        $this->singleValueMapper = new SingleValueMapper($comparator);
        $this->collectionMapper = new CollectionMapper($comparator);
    }

    public function mapDataToForms($data, $forms): void
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
            $accessor = $this->getAccessor($options);

            /** @psalm-var mixed $value */
            $value = $accessor->read($options, $data, $form);
            $form->setData($value);
        }

        $this->defaultMapper->mapDataToForms($data, $unmappedForms);
    }

    public function mapFormsToData($forms, &$data): void
    {
        $unmappedForms = [];
        foreach ($forms as $name => $form) {
            $config = $form->getConfig();
            /** @psalm-var O $options */
            $options = $config->getOptions();
            $getter = $options['get_value'];

            if ($getter && $config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && !$form->isDisabled()) {
                $accessor = $this->getAccessor($options);
                $accessor->update($options, $data, $form);
            } else {
                $unmappedForms[] = $form;
            }
        }

        $this->defaultMapper->mapFormsToData($unmappedForms, $data);
    }

    /**
     * @psalm-param O $options
     */
    private function getAccessor(array $options): MapperInterface
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
