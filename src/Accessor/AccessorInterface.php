<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

interface AccessorInterface
{
    public function update($entity, $newValue, array $config): void;
}
