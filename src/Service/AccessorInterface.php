<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Service;

interface AccessorInterface
{
    /**
     * @param array|object $object
     * @param mixed        $newValue
     * @param array        $config
     *
     * @psalm-param array{get_value: callable, update_value: callable, add_value: callable, remove_value: callable} $config
     */
    public function update($object, $newValue, array $config): void;
}
