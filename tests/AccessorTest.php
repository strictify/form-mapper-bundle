<?php

declare(strict_types=1);

namespace Strictify\FormMapper\Tests;

use DateTimeInterface;
use Strictify\FormMapper\Tests\Application\Factory\AccessorBuilder;
use Strictify\FormMapper\Tests\Application\Entity\User;
use PHPUnit\Framework\TestCase;
use DateTime;
use Strictify\FormMapper\Tests\Application\Repository\TestRepository;

class AccessorTest extends TestCase
{
    private $accessor;
    private $user;
    private $firstNameConfig;
    private $dobConfig;
    private $tagsConfig;

    protected function setUp(): void
    {
        $this->accessor = (new AccessorBuilder())->getAccessor();
        $this->user = (new TestRepository())->getArnold();
        $this->firstNameConfig = [
            'get_value' => static function (User $user) {
                return $user->getName();
            },
            'update_value' => static function (string $firstName, User $user): void {
                $user->setName($firstName);
            },
        ];
        $this->dobConfig = [
            'get_value' => static function (User $user) {
                return $user->getDob();
            },
            'update_value' => static function (DateTimeInterface $dob, User $user): void {
                $user->setDob($dob);
            },
        ];
        $this->tagsConfig = [
            'get_value' => static function (User $user) {
                return $user->getTags();
            },
            'add_value' => static function (string $tag, User $user): void {
                $user->addTag($tag);
            },
            'remove_value' => static function (string $tag, User $user): void {
                $user->removeTag($tag);
            },
        ];
    }

    public function testSimpleScalar(): void
    {
        $user = $this->user;

        $this->accessor->update($user, 'Terminator', $this->firstNameConfig);
        $this->assertSame('Terminator', $user->getName());
    }

    /**
     * If different DateTime object is submitted, assert that updater will not be called.
     */
    public function testDateTimeObject(): void
    {
        $user = $this->user;

        $dob = $user->getDob();
        // same date, different object
        $submittedDob = new DateTime('2015-01-01 12:00:00');
        $this->accessor->update($user, $submittedDob, $this->dobConfig);
        $this->assertSame($dob, $user->getDob());

        // different date
        $submittedDob = new DateTime('2015-01-01 12:00:01');
        $this->accessor->update($user, $submittedDob, $this->dobConfig);
        $this->assertNotSame($dob, $user->getDob());
    }

    public function testScalarAdder(): void
    {
        $user = $this->user;

        $submitted = ['Strong', 'Big'];
        $this->accessor->update($user, $submitted, $this->tagsConfig);
        $this->assertSame(['Strong', 'Big'], $user->getTags());
    }

    public function testScalarRemover(): void
    {
        $user = $this->user;

        $submitted = [1 => 'Carl'];
        $this->accessor->update($user, $submitted, $this->tagsConfig);
        $this->assertSame([1 => 'Carl'], $user->getTags());
    }
}
