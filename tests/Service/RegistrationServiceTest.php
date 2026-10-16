<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RegistrationService;
use App\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Creation of a client account.
 */
#[TestDox('Inscription d\'un utilisateur (service)')]
class RegistrationServiceTest extends DatabaseTestCase
{
    use MailerAssertionsTrait;

    #[TestDox('Le compte est créé avec le rôle client, inactif, et un e-mail d\'activation est envoyé')]
    public function testRegisterCreatesAnInactiveClientAccount(): void
    {
        $user = (new User())->setEmail('Marie@Test.fr')->setFirstName('Marie')->setLastName('Curie');

        $emailSent = $this->service(RegistrationService::class)->register($user, 'Guitare!2026ok');
        $this->em->clear();

        $saved = $this->service(UserRepository::class)->findOneByEmail('marie@test.fr');
        self::assertTrue($emailSent);
        self::assertNotNull($saved);
        self::assertSame(['ROLE_CLIENT', 'ROLE_USER'], $saved->getRoles());
        self::assertFalse($saved->isVerified());

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertEmailAddressContains($email, 'To', 'marie@test.fr');
        self::assertEmailHtmlBodyContains($email, '/verify/email?');
    }

    #[TestDox('Sécurité : le mot de passe est haché en base, jamais enregistré en clair')]
    public function testPasswordIsHashed(): void
    {
        $user = (new User())->setEmail('marie@test.fr')->setFirstName('Marie')->setLastName('Curie');
        $this->service(RegistrationService::class)->register($user, 'Guitare!2026ok');
        $this->em->clear();

        $saved = $this->service(UserRepository::class)->findOneByEmail('marie@test.fr');
        self::assertNotSame('Guitare!2026ok', $saved->getPassword());
        self::assertStringNotContainsString('Guitare', $saved->getPassword());
        self::assertTrue($this->service(UserPasswordHasherInterface::class)->isPasswordValid($saved, 'Guitare!2026ok'));
        self::assertFalse($this->service(UserPasswordHasherInterface::class)->isPasswordValid($saved, 'mauvais'));
    }

    #[TestDox('Sécurité : un rôle administrateur fourni à l\'inscription est remplacé par le rôle client')]
    public function testRegistrationCannotGrantAdminRole(): void
    {
        $user = (new User())->setEmail('pirate@test.fr')->setFirstName('P')->setLastName('P')->setRoles([User::ROLE_ADMIN]);

        $this->service(RegistrationService::class)->register($user, 'Guitare!2026ok');

        self::assertSame(['ROLE_CLIENT', 'ROLE_USER'], $user->getRoles());
        self::assertFalse($user->isAdmin());
    }
}
