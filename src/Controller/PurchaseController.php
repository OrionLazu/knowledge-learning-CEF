<?php

namespace App\Controller;

use App\Entity\Contract\PurchasableInterface;
use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\Purchase;
use App\Entity\User;
use App\Exception\PurchaseException;
use App\Repository\PurchaseRepository;
use App\Service\PurchaseService;
use Psr\Log\LoggerInterface;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Purchase of a course or a lesson with Stripe Checkout (test mode).
 */
#[Route('/purchase')]
class PurchaseController extends AbstractController
{
    public function __construct(
        private readonly PurchaseService $purchaseService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/course/{id<\d+>}', name: 'app_purchase_course', methods: ['POST'])]
    public function buyCourse(Course $course, Request $request): Response
    {
        return $this->checkout($course, 'buy_course_'.$course->getId(), $request, $course);
    }

    #[Route('/lesson/{id<\d+>}', name: 'app_purchase_lesson', methods: ['POST'])]
    public function buyLesson(Lesson $lesson, Request $request): Response
    {
        return $this->checkout($lesson, 'buy_lesson_'.$lesson->getId(), $request, $lesson->getCourse());
    }

    /**
     * Return page after a successful payment: Stripe is asked to confirm it, then the lessons are unlocked.
     */
    #[Route('/success', name: 'app_purchase_success', methods: ['GET'])]
    public function success(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $sessionId = (string) $request->query->get('session_id');

        try {
            $purchase = $this->purchaseService->confirmPayment($sessionId, $user);
        } catch (PurchaseException $exception) {
            $this->addFlash('danger', $exception->getMessage());

            return $this->redirectToRoute('app_home');
        } catch (ApiErrorException $exception) {
            $this->logger->error('Stripe payment check failed.', ['exception' => $exception]);
            $this->addFlash('danger', 'Le paiement n\'a pas pu être vérifié. Rechargez la page dans quelques instants.');

            return $this->redirectToRoute('app_home');
        }

        return $this->render('purchase/success.html.twig', ['purchase' => $purchase]);
    }

    /**
     * Return page when the client leaves the Stripe payment page without paying.
     */
    #[Route('/{id<\d+>}/cancel', name: 'app_purchase_cancel', methods: ['GET'])]
    public function cancel(Purchase $purchase): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        // Only the owner of the order can cancel it.
        if ($purchase->getCustomer()->getId() === $user->getId()) {
            $this->purchaseService->cancel($purchase);
        }
        $this->addFlash('warning', 'Le paiement a été annulé : aucun montant n\'a été débité.');

        return $this->redirectToRoute('app_home');
    }

    /**
     * History of the paid orders of the logged-in user.
     */
    #[Route('/history', name: 'app_purchase_history', methods: ['GET'])]
    public function history(PurchaseRepository $purchaseRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('purchase/history.html.twig', [
            'purchases' => $purchaseRepository->findPaidForUser($user),
        ]);
    }

    /**
     * Common steps of both purchases: CSRF check, order creation, redirection to Stripe.
     */
    private function checkout(PurchasableInterface $product, string $csrfTokenId, Request $request, Course $backTo): Response
    {
        $back = $this->redirectToRoute('app_course_show', ['id' => $backTo->getId()]);

        if (!$this->isCsrfTokenValid($csrfTokenId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Votre session a expiré, veuillez réessayer.');

            return $back;
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $purchase = $this->purchaseService->createPurchase($user, $product);
        } catch (PurchaseException $exception) {
            $this->addFlash('warning', $exception->getMessage());

            return $back;
        }

        try {
            $paymentUrl = $this->purchaseService->startPayment(
                $purchase,
                // Stripe replaces {CHECKOUT_SESSION_ID} with the identifier of the session.
                $this->generateUrl('app_purchase_success', [], UrlGeneratorInterface::ABSOLUTE_URL).'?session_id={CHECKOUT_SESSION_ID}',
                $this->generateUrl('app_purchase_cancel', ['id' => $purchase->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            );
        } catch (ApiErrorException|\LogicException $exception) {
            $this->logger->error('The Stripe payment could not be started.', ['exception' => $exception]);
            $this->purchaseService->cancel($purchase);
            $this->addFlash('danger', 'Le paiement est momentanément indisponible, veuillez réessayer plus tard.');

            return $back;
        }

        // 303: the browser follows the redirection with a GET request.
        return new RedirectResponse($paymentUrl, Response::HTTP_SEE_OTHER);
    }
}
