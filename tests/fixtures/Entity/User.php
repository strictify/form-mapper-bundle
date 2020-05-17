<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Fixture\Entity;

use function array_search;

class User
{
    private string $firstName;
    private string $lastName;

    /** @var array<Movie> */
    private array $favoriteMovies;

    /** @param Movie[] $favoriteMovies */
    public function __construct(string $firstName, string $lastName, array $favoriteMovies = [])
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->favoriteMovies = $favoriteMovies;
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
}
