<?php

declare(strict_types=1);

namespace Strictify\FormMapper\DataMapper;

use Closure;
use Strictify\FormMapper\VO\SubmittedData;
use Strictify\FormMapper\Accessor\Accessor;
use Strictify\FormMapper\Service\Comparator;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Extension\Core\DataMapper\PropertyPathMapper;

/**
 * @see SubmittedData
 *
 * @psalm-type O=array{
 *      compare: callable,
 *      get_value: ?Closure,
 *      update_value: Closure,
 *      add_value: Closure,
 *      remove_value: Closure,
 *      prototype?: bool,
 *      _lazy_getter_values: SubmittedData,
 * }
 *
 * @see Closure
 */
class StrictFormMapper implements DataMapperInterface
{
    private DataMapperInterface $defaultMapper;
    private Accessor $accessor;

    public function __construct(?DataMapperInterface $defaultMapper, Comparator $comparator)
    {
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->defaultMapper = $defaultMapper ?: new PropertyPathMapper();
        $this->accessor = new Accessor($comparator);
    }

    public function mapDataToForms($data, $forms): void
    {
        $unmappedForms = [];

        foreach ($forms as $form) {
            /** @psalm-var O $options */
            $options = $form->getConfig()->getOptions();
            $getter = $options['get_value'];
            $isCollection = $this->isCollection($options);

            if (!$getter) {
                $unmappedForms[] = $form;
                continue;
            }
            /** @var mixed $defaultData */
            $defaultData = $this->accessor->read($getter, $data, $isCollection);
            $options['_lazy_getter_values']->setStore($defaultData);

            $form->setData($defaultData);
        }

        $this->defaultMapper->mapDataToForms($data, $unmappedForms);
    }

    public function mapFormsToData($forms, &$data): void
    {
        $unmappedForms = [];
        foreach ($forms as $form) {
            $config = $form->getConfig();
            /** @psalm-var O $options */
            $options = $config->getOptions();
            $getter = $options['get_value'];
            $compare = $options['compare'];
            $isCollection = $this->isCollection($options);
            /** @var SubmittedData $lazyGetter */
            $lazyGetter = $options['_lazy_getter_values'];

            if ($getter && $config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && !$form->isDisabled()) {
                $updater = $options['update_value'];

                $isCollection
                    ? $this->accessor->writeCollection($compare, $lazyGetter, $options['add_value'], $options['remove_value'], $data, $form->getData())
                    : $this->accessor->write($compare, $lazyGetter, $updater, $data, $form->getData());
            } else {
                $unmappedForms[] = $form;
            }
        }

        $this->defaultMapper->mapFormsToData($unmappedForms, $data);
    }

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
