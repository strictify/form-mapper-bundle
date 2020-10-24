<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use Strictify\FormMapper\Types;
use Strictify\FormMapper\Store;
use Symfony\Component\Form\FormInterface;

/**
 * @psalm-import-type O from Types
 *
 * @see Types
 */
interface MapperInterface
{
    /**
     * @psalm-param O $options
     * @psalm-param mixed $data
     *
     * @return mixed
     */
    public function read(array $options, $data, FormInterface $form);

    /**
     * @psalm-param O $options
     * @psalm-param mixed $data
     */
    public function update(array $options, &$data, FormInterface $form, ?Store $store): void;
}
