<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

/**
 * Registration of clients and activation of their account by e-mail.
 */
class RegistrationController extends AbstractController
{
    public function __construct(private readonly RegistrationService $registrationService)
    {
    }

    /**
     * Registration form: the account is created inactive and an activation link is e-mailed.
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            if ($this->registrationService->register($user, $plainPassword)) {
                $this->addFlash('success', sprintf('Votre compte est créé. Cliquez sur le lien envoyé à %s pour l\'activer.', $user->getEmail()));
            } else {
                $this->addFlash('warning', 'Votre compte est créé mais l\'e-mail d\'activation n\'a pas pu être envoyé. Connectez-vous pour le recevoir à nouveau.');
            }

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    /**
     * Link received by e-mail: activates the account, logs the user in and opens the catalogue.
     */
    #[Route('/verify/email', name: 'app_verify_email', methods: ['GET'])]
    public function verifyUserEmail(Request $request, UserRepository $userRepository, EmailVerifier $emailVerifier, Security $security): Response
    {
        $id = $request->query->get('id');
        $user = null !== $id && ctype_digit($id) ? $userRepository->find((int) $id) : null;

        if (null === $user) {
            $this->addFlash('danger', 'Lien d\'activation invalide.');

            return $this->redirectToRoute('app_register');
        }

        // The signature is checked every time, even for an active account:
        // otherwise the URL "?id=..." alone would be enough to log in as somebody else.
        try {
            $emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface) {
            $this->addFlash('danger', 'Ce lien d\'activation est invalide ou a expiré. Connectez-vous pour en recevoir un nouveau.');

            return $this->redirectToRoute('app_login');
        }

        $security->login($user, 'form_login', 'main');
        $this->addFlash('success', 'Votre compte est activé : vous pouvez maintenant acheter des cursus et des leçons.');

        return $this->redirectToRoute('app_home');
    }

    /**
     * Sends a new activation link to the logged-in user whose account is not active yet.
     */
    #[Route('/account/resend-activation', name: 'app_verify_resend', methods: ['POST'])]
    public function resend(
        Request $request,
        #[Autowire(service: 'limiter.activation_email')] RateLimiterFactoryInterface $activationEmailLimiter,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('resend_activation', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Votre session a expiré, veuillez réessayer.');

            return $this->redirectToRoute('app_home');
        }

        if ($user->isVerified()) {
            return $this->redirectToRoute('app_home');
        }

        // Limits the number of e-mails so that nobody can flood a mailbox.
        if (!$activationEmailLimiter->create((string) $user->getId())->consume()->isAccepted()) {
            $this->addFlash('danger', 'Trop de demandes : réessayez dans quelques minutes.');

            return $this->redirectToRoute('app_home');
        }

        if ($this->registrationService->sendActivationEmail($user)) {
            $this->addFlash('success', sprintf('Un nouveau lien d\'activation a été envoyé à %s.', $user->getEmail()));
        } else {
            $this->addFlash('danger', 'L\'e-mail n\'a pas pu être envoyé, réessayez plus tard.');
        }

        return $this->redirectToRoute('app_home');
    }
}
