<?php

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Sends and checks the account activation link.
 *
 * The link is signed (HMAC with the application secret) and expires after one hour:
 * it can neither be guessed nor modified to activate somebody else's account.
 */
class EmailVerifier
{
    public function __construct(
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $senderEmail,
    ) {
    }

    /**
     * Sends the e-mail containing the signed activation link.
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface if the e-mail cannot be sent
     */
    public function sendEmailConfirmation(User $user): void
    {
        $signature = $this->verifyEmailHelper->generateSignature(
            'app_verify_email',
            (string) $user->getId(),
            (string) $user->getEmail(),
            ['id' => $user->getId()],
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->senderEmail, 'Knowledge Learning'))
            ->to(new Address((string) $user->getEmail(), $user->getFullName()))
            ->subject('Knowledge Learning - Activez votre compte')
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'user' => $user,
                'signedUrl' => $signature->getSignedUrl(),
                'expiresAtMessageKey' => $signature->getExpirationMessageKey(),
                'expiresAtMessageData' => $signature->getExpirationMessageData(),
            ]);

        $this->mailer->send($email);
    }

    /**
     * Checks the signature of the clicked link and activates the account.
     *
     * @throws VerifyEmailExceptionInterface if the link is invalid or expired
     */
    public function handleEmailConfirmation(Request $request, User $user): void
    {
        $this->verifyEmailHelper->validateEmailConfirmationFromRequest($request, (string) $user->getId(), (string) $user->getEmail());

        $user->setIsVerified(true);
        $this->entityManager->flush();
    }
}
