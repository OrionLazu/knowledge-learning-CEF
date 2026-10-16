<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\ProgressException;
use App\Repository\LessonRepository;
use App\Repository\LessonValidationRepository;
use App\Security\Voter\LessonVoter;
use App\Service\ProgressService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Lesson pages, only reachable after buying the lesson or its course.
 */
#[Route('/lesson')]
class LessonController extends AbstractController
{
    public function __construct(private readonly LessonRepository $lessonRepository)
    {
    }

    /**
     * Displays the lesson sheet and its video.
     */
    #[Route('/{id<\d+>}', name: 'app_lesson_show', methods: ['GET'])]
    public function show(int $id, LessonValidationRepository $lessonValidationRepository): Response
    {
        $lesson = $this->lessonRepository->findWithCourse($id) ?? throw $this->createNotFoundException();

        if (!$this->isGranted(LessonVoter::VIEW, $lesson)) {
            $this->addFlash('warning', 'Achetez cette leçon ou son cursus pour y accéder.');

            return $this->redirectToRoute('app_course_show', ['id' => $lesson->getCourse()->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
            'isValidated' => $lessonValidationRepository->isValidated($user, $lesson),
        ]);
    }

    /**
     * Button "Valider la leçon": marks the lesson as completed, and the course / theme if they are complete.
     */
    #[Route('/{id<\d+>}/validate', name: 'app_lesson_validate', methods: ['POST'])]
    public function validate(int $id, Request $request, ProgressService $progressService): Response
    {
        $lesson = $this->lessonRepository->findWithCourse($id) ?? throw $this->createNotFoundException();

        if (!$this->isCsrfTokenValid('validate_lesson_'.$lesson->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Votre session a expiré, veuillez réessayer.');

            return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $result = $progressService->validateLesson($user, $lesson);
        } catch (ProgressException $exception) {
            $this->addFlash('warning', $exception->getMessage());

            return $this->redirectToRoute('app_course_show', ['id' => $lesson->getCourse()->getId()]);
        }

        if ($result->lessonValidated) {
            $this->addFlash('success', 'Leçon validée, bravo !');
        }
        if ($result->courseValidated) {
            $this->addFlash('success', sprintf('Vous avez validé toutes les leçons : le cursus « %s » est validé.', $lesson->getCourse()->getTitle()));
        }
        if (null !== $result->certifiedTheme) {
            $this->addFlash('success', sprintf('Félicitations ! Vous obtenez la certification Knowledge Learning « %s ».', $result->certifiedTheme->getName()));

            return $this->redirectToRoute('app_certifications');
        }

        return $this->redirectToRoute('app_lesson_show', ['id' => $lesson->getId()]);
    }
}
