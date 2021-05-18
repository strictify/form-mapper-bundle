<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Fixture\Entity;

class Movie
{
    public function __construct(private string $name)
    {
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }
}
