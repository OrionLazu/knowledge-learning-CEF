<?php

namespace App\Tests\Service;

use App\Exception\ProgressException;
use App\Repository\CertificationRepository;
use App\Repository\CourseValidationRepository;
use App\Service\ProgressService;
use App\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Validation of lessons and courses, and certification of a theme.
 */
#[TestDox('Validation des leçons, des cursus et certification')]
class ProgressServiceTest extends DatabaseTestCase
{
    #[TestDox('Valider une leçon achetée l\'enregistre ; le cursus n\'est pas encore validé')]
    public function testValidateOneLesson(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $this->grantAccess($user, $this->lesson($theme, 1));

        $result = $this->service(ProgressService::class)->validateLesson($user, $this->lesson($theme, 1));

        self::assertTrue($result->lessonValidated);
        self::assertFalse($result->courseValidated);
        self::assertNull($result->certifiedTheme);
    }

    #[TestDox('Une leçon non achetée ne peut pas être validée')]
    public function testLessonMustBeBoughtFirst(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();

        $this->expectException(ProgressException::class);
        $this->service(ProgressService::class)->validateLesson($user, $this->lesson($theme, 1));
    }

    #[TestDox('Valider toutes les leçons d\'un cursus valide automatiquement le cursus')]
    public function testCourseIsValidatedAutomatically(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme('Musique', [2, 2]);
        foreach ($theme->getLessons() as $lesson) {
            $this->grantAccess($user, $lesson);
        }
        $progress = $this->service(ProgressService::class);

        $progress->validateLesson($user, $this->lesson($theme, 1, 0));
        $result = $progress->validateLesson($user, $this->lesson($theme, 2, 0));

        self::assertTrue($result->courseValidated);
        self::assertNull($result->certifiedTheme, 'The second course of the theme is not completed yet.');
        self::assertTrue($this->service(CourseValidationRepository::class)->isValidated($user, $theme->getCourses()->get(0)));
        self::assertFalse($this->service(CourseValidationRepository::class)->isValidated($user, $theme->getCourses()->get(1)));
    }

    #[TestDox('Valider toutes les leçons d\'un thème délivre la certification Knowledge Learning')]
    public function testThemeCertificationIsEarned(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme('Musique', [2, 2]);
        $progress = $this->service(ProgressService::class);
        $result = null;
        foreach ($theme->getLessons() as $lesson) {
            $this->grantAccess($user, $lesson);
            $result = $progress->validateLesson($user, $lesson);
        }

        self::assertSame($theme, $result?->certifiedTheme);
        $certifications = $this->service(CertificationRepository::class)->findForUser($user);
        self::assertCount(1, $certifications);
        self::assertSame('Musique', $certifications[0]->getTheme()->getName());
    }

    #[TestDox('Valider deux fois la même leçon ne crée aucun doublon')]
    public function testValidationIsIdempotent(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme('Jardinage', [1]);
        $this->grantAccess($user, $this->lesson($theme, 1));
        $progress = $this->service(ProgressService::class);

        $first = $progress->validateLesson($user, $this->lesson($theme, 1));
        $second = $progress->validateLesson($user, $this->lesson($theme, 1));

        self::assertTrue($first->lessonValidated);
        self::assertSame($theme, $first->certifiedTheme);
        self::assertFalse($second->lessonValidated);
        self::assertNull($second->certifiedTheme);
        self::assertCount(1, $this->service(CertificationRepository::class)->findForUser($user));
    }
}
