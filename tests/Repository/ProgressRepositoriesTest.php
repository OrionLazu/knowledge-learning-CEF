<?php

namespace App\Tests\Repository;

use App\Entity\Certification;
use App\Entity\CourseValidation;
use App\Entity\LessonValidation;
use App\Repository\CertificationRepository;
use App\Repository\CourseValidationRepository;
use App\Repository\LessonValidationRepository;
use App\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Data access components of the progress: validated lessons, validated courses, certifications.
 */
#[TestDox('Repositories de la progression (leçons validées, cursus validés, certifications)')]
class ProgressRepositoriesTest extends DatabaseTestCase
{
    #[TestDox('Les leçons validées sont comptées par cursus et par thème')]
    public function testValidatedLessonsAreCountedByCourseAndTheme(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme('Cuisine', [2, 2]);
        $now = new \DateTimeImmutable();
        $this->em->persist(new LessonValidation($user, $this->lesson($theme, 1, 0), $now));
        $this->em->persist(new LessonValidation($user, $this->lesson($theme, 2, 0), $now));
        $this->em->persist(new LessonValidation($user, $this->lesson($theme, 1, 1), $now));
        $this->em->flush();

        $repository = $this->service(LessonValidationRepository::class);
        self::assertSame(2, $repository->countValidatedInCourse($user, $theme->getCourses()->get(0)));
        self::assertSame(1, $repository->countValidatedInCourse($user, $theme->getCourses()->get(1)));
        self::assertSame(3, $repository->countValidatedInTheme($user, $theme));
        self::assertTrue($repository->isValidated($user, $this->lesson($theme, 1, 1)));
        self::assertFalse($repository->isValidated($user, $this->lesson($theme, 2, 1)));
        self::assertCount(3, $repository->findValidatedLessonIds($user));
    }

    #[TestDox('Sécurité : la progression d\'un client n\'est jamais comptée pour un autre client')]
    public function testProgressIsIsolatedBetweenUsers(): void
    {
        $user = $this->createUser('a@test.fr');
        $other = $this->createUser('b@test.fr');
        $theme = $this->createTheme();
        $now = new \DateTimeImmutable();
        $this->em->persist(new LessonValidation($user, $this->lesson($theme, 1), $now));
        $this->em->persist(new CourseValidation($user, $this->firstCourse($theme), $now));
        $this->em->persist(new Certification($user, $theme, $now));
        $this->em->flush();

        self::assertSame(0, $this->service(LessonValidationRepository::class)->countValidatedInTheme($other, $theme));
        self::assertSame([], $this->service(CourseValidationRepository::class)->findValidatedCourseIds($other));
        self::assertSame([], $this->service(CertificationRepository::class)->findForUser($other));
        self::assertFalse($this->service(CertificationRepository::class)->hasCertification($other, $theme));
    }

    #[TestDox('Les cursus validés d\'un client sont retrouvés')]
    public function testValidatedCourses(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme('Musique', [2, 2]);
        $this->em->persist(new CourseValidation($user, $theme->getCourses()->get(1), new \DateTimeImmutable()));
        $this->em->flush();

        $repository = $this->service(CourseValidationRepository::class);
        self::assertSame([$theme->getCourses()->get(1)->getId()], $repository->findValidatedCourseIds($user));
        self::assertTrue($repository->isValidated($user, $theme->getCourses()->get(1)));
        self::assertFalse($repository->isValidated($user, $theme->getCourses()->get(0)));
    }

    #[TestDox('Les certifications d\'un client sont listées de la plus récente à la plus ancienne')]
    public function testCertificationsAreSortedFromTheMostRecent(): void
    {
        $user = $this->createUser();
        $music = $this->createTheme('Musique');
        $garden = $this->createTheme('Jardinage');
        $this->em->persist(new Certification($user, $music, new \DateTimeImmutable('2026-01-10')));
        $this->em->persist(new Certification($user, $garden, new \DateTimeImmutable('2026-03-05')));
        $this->em->flush();

        $certifications = $this->service(CertificationRepository::class)->findForUser($user);

        self::assertSame(['Jardinage', 'Musique'], array_map(static fn ($c) => $c->getTheme()->getName(), $certifications));
    }
}
