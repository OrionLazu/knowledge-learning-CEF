<?php

namespace App\Tests\Repository;

use App\Entity\Course;
use App\Repository\CourseRepository;
use App\Repository\LessonRepository;
use App\Repository\ThemeRepository;
use App\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Data access components of the catalogue: themes, courses and lessons.
 */
#[TestDox('Repositories du catalogue (thèmes, cursus, leçons)')]
class CatalogueRepositoryTest extends DatabaseTestCase
{
    #[TestDox('Le catalogue complet est chargé avec les cursus et les leçons dans l\'ordre')]
    public function testFindCatalogueLoadsThemesCoursesAndLessons(): void
    {
        $this->createTheme('Musique', [2, 2]);
        $this->createTheme('Jardinage', [2]);
        $this->em->clear();

        $themes = $this->service(ThemeRepository::class)->findCatalogue();

        self::assertSame(['Musique', 'Jardinage'], array_map(static fn ($t) => $t->getName(), $themes));
        self::assertCount(2, $themes[0]->getCourses());
        self::assertSame(['Leçon 1', 'Leçon 2'], array_map(
            static fn ($l) => $l->getTitle(),
            $themes[0]->getCourses()->first()->getLessons()->toArray(),
        ));
        self::assertCount(4, $themes[0]->getLessons());
    }

    #[TestDox('Une leçon est chargée avec son cursus et son thème ; un identifiant inconnu renvoie null')]
    public function testFindLessonWithCourse(): void
    {
        $theme = $this->createTheme();
        $lessonId = $this->lesson($theme, 2)->getId();
        $this->em->clear();

        $repository = $this->service(LessonRepository::class);
        $lesson = $repository->findWithCourse($lessonId);

        self::assertSame('Leçon 2', $lesson?->getTitle());
        self::assertSame('Musique', $lesson->getCourse()->getTheme()->getName());
        self::assertNull($repository->findWithCourse(999999));
    }

    #[TestDox('Un cursus est enregistré avec son prix en centimes et son thème')]
    public function testCourseIsPersisted(): void
    {
        $theme = $this->createTheme();
        $courseId = $this->firstCourse($theme)->getId();
        $this->em->clear();

        /** @var Course $course */
        $course = $this->service(CourseRepository::class)->find($courseId);
        self::assertSame(5000, $course->getPrice());
        self::assertSame('Musique', $course->getTheme()->getName());
        self::assertCount(2, $course->getIncludedLessons());
    }

    #[TestDox('Supprimer un thème supprime ses cursus et ses leçons (cascade en base)')]
    public function testDeletingAThemeDeletesItsContent(): void
    {
        $theme = $this->createTheme();
        $this->em->remove($theme);
        $this->em->flush();

        self::assertSame(0, $this->service(CourseRepository::class)->count([]));
        self::assertSame(0, $this->service(LessonRepository::class)->count([]));
    }
}
