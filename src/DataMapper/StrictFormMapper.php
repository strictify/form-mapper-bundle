<?php

declare(strict_types=1);

namespace Strictify\FormMapper\DataMapper;

use Symfony\Component\Form\DataMapperInterface;

class StrictFormMapper implements DataMapperInterface
{
    private $defaultMapper;

    public function __construct(DataMapperInterface $defaultMapper)
    {
        $this->defaultMapper = $defaultMapper;
    }

    public function mapDataToForms($viewData, $forms): void
    {
    }

    public function mapFormsToData($forms, &$viewData): void
    {
    }
}
