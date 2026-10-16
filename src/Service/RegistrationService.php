<?php

namespace App\Service;

use App\Entity\User;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creation of client accounts.
 */
class RegistrationService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly EmailVerifier $emailVerifier,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Saves a new account and sends its activation e-mail.
     *
     * - the account automatically gets the client role;
     * - the password is hashed, the clear text is never saved;
     * - the account stays inactive until the activation link is clicked.
     *
     * @param string $plainPassword password already checked by the {@see \App\Validator\StrongPassword} policy
     *
     * @return bool true if the activation e-mail was sent, false if the mail server failed
     *              (the account is created anyway, the e-mail can be sent again later)
     */
    public function register(User $user, string $plainPassword): bool
    {
        $user->setRoles([User::ROLE_CLIENT]);
        $user->setIsVerified(false);
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->sendActivationEmail($user);
    }

    /**
     * Sends (again) the activation e-mail of an account.
     *
     * @return bool false if the mail server could not be reached
     */
    public function sendActivationEmail(User $user): bool
    {
        try {
            $this->emailVerifier->sendEmailConfirmation($user);

            return true;
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('The activation e-mail could not be sent.', ['exception' => $exception, 'user' => $user->getId()]);

            return false;
        }
    }
}
