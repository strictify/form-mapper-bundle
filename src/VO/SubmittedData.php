<?php

declare(strict_types=1);

namespace Strictify\FormMapper\VO;

/**
 * Used to avoid calling getter multiple times; first one when mapping from data to forms, send time when comparing default values with submitted values.
 */
class SubmittedData
{
    private bool $isSet = false;
    private mixed $store = null;

    /**
     * @return mixed
     */
    public function getStore()
    {
        return $this->store;
    }

    public function setStore(mixed $store): void
    {
        $this->store = $store;
        $this->isSet = true;
    }

    public function isPopulated(): bool
    {
        return $this->isSet;
    }
}
