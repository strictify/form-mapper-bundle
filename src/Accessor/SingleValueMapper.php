<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use ReflectionFunction;
use Strictify\FormMapper\Store;
use Symfony\Component\Form\FormInterface;

class SingleValueMapper extends AbstractMapper
{
    public function update(array $options, &$data, FormInterface $form, ?Store $store): void
    {
        /** @psalm-var mixed $originalValue */
        $originalValue = $this->read($options, $data, $form);
        /** @psalm-var mixed $submittedData */
        $submittedData = $form->getData();
        $compare = $options['compare'];
        $updater = $options['update_value'];
        $reflection = new ReflectionFunction($updater);

        // values are identical; do not call updater
        if ($this->isEqual($compare, $originalValue, $submittedData)) {
            return;
        }
        $params = $reflection->getParameters();

        // if closure doesn't have params, it is equivalent of mapped: false but only for writer
        $firstParam = $params[0] ?? null;
        if (!$firstParam) {
            return;
        }
        $firstParameterType = $firstParam->getType();

        // first param does not accept submitted null value; do not call updater
        if (null === $submittedData && $firstParameterType && !$firstParameterType->allowsNull()) {
            return;
        }
        $secondParam = $params[1] ?? null;

        // second parameter doesn't exist; form can still be submitted
        if (!$secondParam) {
            $reflection->invoke($submittedData);

            return;
        }

        // second parameter doesn't allow null; do nothing
        if (null === $data && !$secondParam->allowsNull()) {
            return;
        }

        // moment of truth
        $this->doCall($reflection, $submittedData, $data);
    }
}
