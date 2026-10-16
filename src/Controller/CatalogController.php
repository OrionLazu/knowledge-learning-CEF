<?php

namespace App\Controller;

use App\Entity\Course;
use App\Entity\User;
use App\Repository\CourseValidationRepository;
use App\Repository\LessonAccessRepository;
use App\Repository\LessonValidationRepository;
use App\Repository\ThemeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public catalogue: themes, courses and the list of their lessons with their prices.
 */
class CatalogController extends AbstractController
{
    public function __construct(
        private readonly LessonAccessRepository $lessonAccessRepository,
        private readonly LessonValidationRepository $lessonValidationRepository,
        private readonly CourseValidationRepository $courseValidationRepository,
    ) {
    }

    /**
     * Home page: every theme with its courses.
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(ThemeRepository $themeRepository): Response
    {
        return $this->render('catalog/index.html.twig', [
            'themes' => $themeRepository->findCatalogue(),
        ] + $this->getProgress());
    }

    /**
     * Page of a course: its lessons, what the user already owns and what can be bought.
     */
    #[Route('/course/{id<\d+>}', name: 'app_course_show', methods: ['GET'])]
    public function course(Course $course): Response
    {
        return $this->render('catalog/course.html.twig', [
            'course' => $course,
        ] + $this->getProgress());
    }

    /**
     * Identifiers of the lessons owned / validated and of the courses validated by the visitor
     * (empty lists for an anonymous visitor).
     *
     * @return array{ownedLessonIds: list<int>, validatedLessonIds: list<int>, validatedCourseIds: list<int>}
     */
    private function getProgress(): array
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return ['ownedLessonIds' => [], 'validatedLessonIds' => [], 'validatedCourseIds' => []];
        }

        return [
            'ownedLessonIds' => $this->lessonAccessRepository->findAccessibleLessonIds($user),
            'validatedLessonIds' => $this->lessonValidationRepository->findValidatedLessonIds($user),
            'validatedCourseIds' => $this->courseValidationRepository->findValidatedCourseIds($user),
        ];
    }
}
