<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Application\Entity;

class User
{
    private string $firstName;

    private string $lastName;

    public function __construct(string $firstName, string $lastName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function changeFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function changeLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }
}
