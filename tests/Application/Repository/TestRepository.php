<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests\Application\Repository;

use DateTime;
use Strictify\FormMapper\Tests\Application\Entity\Movie;
use Strictify\FormMapper\Tests\Application\Entity\User;

/**
 * Used to simulate Doctrines' identity map.
 */
class TestRepository
{
    private $user;

    private $predator;
    private $terminator;

    public function getArnold(): User
    {
        if (null === $this->user) {
            $terminator = $this->getTerminatorMovie();
            $predator = $this->getPredatorMovie();
            $this->predator = $predator;
            $this->terminator = $terminator;
            $this->user = new User('Arnold', new DateTime('2015-01-01 12:00:00'), [$terminator, $predator]);
        }

        return $this->user;
    }

    public function getTerminatorMovie(): Movie
    {
        if (null === $this->terminator) {
            $this->terminator = new Movie('Terminator');
        }

        return $this->terminator;
    }

    public function getPredatorMovie(): Movie
    {
        if (null === $this->predator) {
            $this->predator = new Movie('Predator');
        }

        return $this->predator;
    }
}
