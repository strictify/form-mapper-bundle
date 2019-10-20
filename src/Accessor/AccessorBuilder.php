<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Accessor;

class AccessorBuilder
{
    public function getAccessor(): AccessorInterface
    {
        return new Accessor();
    }
}
