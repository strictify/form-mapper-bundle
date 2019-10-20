<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Application\Entity;

use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;

class User
{
    private $name;
    private $dob;
    private $tags;
    private $movies;

    public function __construct(string $name, DateTimeInterface $dob, array $movies)
    {
        $this->name = $name;
        $this->dob = $dob;
        $this->tags = new ArrayCollection([0 => 'Strong']);
        $this->movies = new ArrayCollection($movies);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDob(): DateTimeInterface
    {
        return $this->dob;
    }

    public function setDob(DateTimeInterface $dob): void
    {
        $this->dob = $dob;
    }

    /**
     * @psalm-return array<array-key, string>
     *
     * @return array
     */
    public function getTags(): array
    {
        return $this->tags->toArray();
    }

    public function addTag(string $tag): void
    {
        $this->tags->add($tag);
    }

    public function removeTag(string $tag): void
    {
        $this->tags->removeElement($tag);
    }

    /**
     * @return Movie[]
     */
    public function getMovies(): array
    {
        return $this->movies->toArray();
    }

    public function addMovie(Movie $movie): void
    {
        $this->movies->add($movie);
    }

    public function removeMovie(Movie $movie): void
    {
        $this->movies->removeElement($movie);
    }
}
