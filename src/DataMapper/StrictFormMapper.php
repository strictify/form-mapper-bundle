<?php

declare(strict_types=1);

namespace Strictify\FormMapper\DataMapper;

use Strictify\FormMapper\Service\AccessorInterface;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Contracts\Translation\TranslatorInterface;
use TypeError;
use function strpos;

class StrictFormMapper implements DataMapperInterface
{
    private $defaultMapper;
    private $accessor;
    private $translator;

    public function __construct(DataMapperInterface $defaultMapper, AccessorInterface $accessor, ?TranslatorInterface $translator)
    {
        $this->defaultMapper = $defaultMapper;
        $this->accessor = $accessor;
        $this->translator = $translator;
    }

    public function mapDataToForms($data, $forms): void
    {
        $unmappedForms = [];

        foreach ($forms as $form) {
            /** @var callable|null $reader */
            $reader = $form->getConfig()->getOption('get_value');
            if (!$reader) {
                $unmappedForms[] = $form;
            } else {
                try {
                    $value = $reader($data);
                    $form->setData($value);
                } catch (TypeError $e) {
                    $form->setData(null);
                }
            }
        }

        $this->defaultMapper->mapDataToForms($data, $unmappedForms);
    }

    public function mapFormsToData($forms, &$data): void
    {
        $unmappedForms = [];
        foreach ($forms as $form) {
            if (!$this->writeFormsToData($form, $data)) {
                $unmappedForms[] = $form;
            }
        }

        $this->defaultMapper->mapFormsToData($unmappedForms, $data);
    }

    /** @param array|object $data */
    private function writeFormsToData(FormInterface $form, &$data): bool
    {
        $config = $form->getConfig();
        /** @var callable|null $reader */
        $reader = $config->getOption('get_value');
        if (!$reader) {
            return false;
        }

        $submittedValue = $form->getData();

        try {
            /** @psalm-param array{get_value: callable, update_value: callable, add_value: callable, remove_value: callable} $options */
            $options = $config->getOptions();
            $this->accessor->update($data, $submittedValue, $options);
        } catch (TypeError $e) {
            $this->addError($submittedValue, $form, $e);
        }

        return true;
    }

    /** @param mixed $submittedValue */
    private function addError($submittedValue, FormInterface $form, TypeError $e): void
    {
        // Second argument is typehinted data object.
        // We are not interested if exception happens on it; it means 'factory' failed and it is parent-level error message.
        if (false !== strpos($e->getMessage(), 'Argument 2 passed to')) {
            return;
        }
        // if there is NotNull constraint on this field, we don't need custom error message; Symfony will take care of it
        if (null === $submittedValue && $this->doesFormHaveNotNullConstraint($form)) {
            return;
        }

        $errorMessage = $form->getConfig()->getOption('write_error_message');
        // do not add errors when adder or remover failed
        if ($errorMessage && !is_countable($submittedValue)) {
            $translatedMessage = $this->translator ? $this->translator->trans($errorMessage) : $errorMessage;
            if (null === $form->getTransformationFailure()) {
                $form->addError(new FormError($translatedMessage, null, [], null, $e));
            }
        }
    }

    private function doesFormHaveNotNullConstraint(FormInterface $form): bool
    {
        $config = $form->getConfig();
        $constraints = $config->getOption('constraints') ?? [];
        foreach ($constraints as $constraint) {
            if ($constraint instanceof NotNull) {
                return true;
            }
        }

        return false;
    }
}
