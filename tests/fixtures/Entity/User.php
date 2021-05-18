<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Fixture\Entity;

use function array_search;

class User
{
    /** @param Movie[] $favoriteMovies */
    public function __construct(
        private string $firstName,
        private string $lastName,
        private array $favoriteMovies = [],
        private bool $isActive = true,
        private ?string $email = null,
    )
    {
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

    /** @return iterable<Movie> */
    public function getFavoriteMovies(): iterable
    {
        return $this->favoriteMovies;
    }

    public function addFavoriteMovie(Movie $movie): void
    {
        $this->favoriteMovies[] = $movie;
    }

    public function removeFavoriteMovie(Movie $movie): void
    {
        $key = array_search($movie, $this->favoriteMovies, true);
        if (false === $key) {
            return;
        }
        unset($this->favoriteMovies[$key]);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}
