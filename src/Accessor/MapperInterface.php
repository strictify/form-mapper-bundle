<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

use Strictify\FormMapper\Types;
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
     */
    public function read(array $options, mixed $data, FormInterface $form): mixed;

    /**
     * @psalm-param O $options
     * @psalm-param mixed $data
     */
    public function update(array $options, mixed &$data, FormInterface $form): void;
}
