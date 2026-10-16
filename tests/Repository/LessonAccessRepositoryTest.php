<?php

namespace App\Tests\Repository;

use App\Entity\LessonAccess;
use App\Repository\LessonAccessRepository;
use App\Tests\Support\DatabaseTestCase;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Data access component of the lesson access rights: functional and security tests.
 */
#[TestDox('Repository des droits d\'accès aux leçons')]
class LessonAccessRepositoryTest extends DatabaseTestCase
{
    private LessonAccessRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->service(LessonAccessRepository::class);
    }

    #[TestDox('Un client a accès uniquement aux leçons achetées')]
    public function testHasAccessOnlyToBoughtLessons(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $this->grantAccess($user, $this->lesson($theme, 1));

        self::assertTrue($this->repository->hasAccess($user, $this->lesson($theme, 1)));
        self::assertFalse($this->repository->hasAccess($user, $this->lesson($theme, 2)));
        self::assertSame([$this->lesson($theme, 1)->getId()], $this->repository->findAccessibleLessonIds($user));
    }

    #[TestDox('Sécurité : les droits d\'un client ne profitent jamais à un autre client')]
    public function testAccessRightsAreIsolatedBetweenUsers(): void
    {
        $buyer = $this->createUser('acheteur@test.fr');
        $other = $this->createUser('autre@test.fr');
        $theme = $this->createTheme();
        $this->grantAccess($buyer, $this->lesson($theme, 1));

        self::assertFalse($this->repository->hasAccess($other, $this->lesson($theme, 1)));
        self::assertSame([], $this->repository->findAccessibleLessonIds($other));
    }

    #[TestDox('Sécurité : une même leçon ne peut pas être accordée deux fois au même client')]
    public function testTheSameAccessCannotBeGrantedTwice(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $this->grantAccess($user, $this->lesson($theme, 1));

        $this->expectException(UniqueConstraintViolationException::class);
        $this->em->persist(new LessonAccess($user, $this->lesson($theme, 1)));
        $this->em->flush();
    }
}
