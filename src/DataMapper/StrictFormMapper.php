<?php

declare(strict_types=1);

namespace Strictify\FormMapper\DataMapper;

use Closure;
use Strictify\FormMapper\Accessor\Accessor;
use Symfony\Component\Form\DataMapperInterface;

/**
 * @see Closure
 */
class StrictFormMapper implements DataMapperInterface
{
    private DataMapperInterface $defaultMapper;
    private Accessor $accessor;

    public function __construct(DataMapperInterface $defaultMapper)
    {
        $this->defaultMapper = $defaultMapper;
        $this->accessor = new Accessor();
    }

    public function mapDataToForms($data, $forms): void
    {
        $unmappedForms = [];

        foreach ($forms as $form) {
            /** @var array{get_value: ?Closure, update_value: ?Closure, add_value: ?Closure, remove_value: ?Closure, prototype?: bool} $options */
            $options = $form->getConfig()->getOptions();
            $getter = $options['get_value'];
            $isCollection = isset($options['prototype']);

            if (!$getter) {
                $unmappedForms[] = $form;
                continue;
            }
            $form->setData($this->accessor->read($getter, $data, $isCollection));
        }

        $this->defaultMapper->mapDataToForms($data, $unmappedForms);
    }

    public function mapFormsToData($forms, &$data): void
    {
        $unmappedForms = [];
        foreach ($forms as $form) {
            /** @var array{get_value: ?Closure, update_value: Closure, add_value: ?Closure, remove_value: ?Closure, prototype?: bool} $options */
            $options = $form->getConfig()->getOptions();
            $getter = $options['get_value'];
            $isCollection = isset($options['prototype']);

            if (!$getter) {
                $unmappedForms[] = $form;
                continue;
            }
            $updater = $options['update_value'];
            $isCollection ? $this->accessor->writeCollection() : $this->accessor->write($getter, $updater, $data, $form->getData());
        }

        $this->defaultMapper->mapFormsToData($unmappedForms, $data);
    }
}
