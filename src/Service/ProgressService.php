<?php

namespace App\Service;

use App\Entity\Certification;
use App\Entity\Course;
use App\Entity\CourseValidation;
use App\Entity\Lesson;
use App\Entity\LessonValidation;
use App\Entity\Theme;
use App\Entity\User;
use App\Exception\ProgressException;
use App\Model\ValidationResult;
use App\Repository\CertificationRepository;
use App\Repository\CourseValidationRepository;
use App\Repository\LessonAccessRepository;
use App\Repository\LessonValidationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Progress of a client: validated lessons, validated courses and certifications.
 *
 * Validating a lesson can trigger two automatic steps:
 * 1. every lesson of the course is validated -> the course is validated;
 * 2. every lesson of the theme is validated  -> the "Knowledge Learning" certification is earned.
 */
class ProgressService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LessonAccessRepository $lessonAccessRepository,
        private readonly LessonValidationRepository $lessonValidationRepository,
        private readonly CourseValidationRepository $courseValidationRepository,
        private readonly CertificationRepository $certificationRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Marks the lesson as completed by the client and applies the automatic validations.
     *
     * Calling it twice for the same lesson changes nothing (idempotent).
     *
     * @throws ProgressException if the client did not buy the lesson
     */
    public function validateLesson(User $user, Lesson $lesson): ValidationResult
    {
        if (!$this->lessonAccessRepository->hasAccess($user, $lesson)) {
            throw new ProgressException('Vous devez acheter cette leçon avant de pouvoir la valider.');
        }

        $result = new ValidationResult();
        if ($this->lessonValidationRepository->isValidated($user, $lesson)) {
            return $result;
        }

        $now = $this->clock->now();
        $this->entityManager->persist(new LessonValidation($user, $lesson, $now));
        // Written immediately so that the counts below include this lesson.
        $this->entityManager->flush();
        $result->lessonValidated = true;

        $course = $lesson->getCourse();
        if ($this->isCourseCompleted($user, $course) && !$this->courseValidationRepository->isValidated($user, $course)) {
            $this->entityManager->persist(new CourseValidation($user, $course, $now));
            $result->courseValidated = true;
        }

        $theme = $course->getTheme();
        if ($this->isThemeCompleted($user, $theme) && !$this->certificationRepository->hasCertification($user, $theme)) {
            $this->entityManager->persist(new Certification($user, $theme, $now));
            $result->certifiedTheme = $theme;
        }

        $this->entityManager->flush();

        return $result;
    }

    /**
     * True when the client validated every lesson of the course.
     */
    public function isCourseCompleted(User $user, Course $course): bool
    {
        $total = $course->getLessons()->count();

        return $total > 0 && $this->lessonValidationRepository->countValidatedInCourse($user, $course) >= $total;
    }

    /**
     * True when the client validated every lesson of every course of the theme.
     */
    public function isThemeCompleted(User $user, Theme $theme): bool
    {
        $total = \count($theme->getLessons());

        return $total > 0 && $this->lessonValidationRepository->countValidatedInTheme($user, $theme) >= $total;
    }
}
