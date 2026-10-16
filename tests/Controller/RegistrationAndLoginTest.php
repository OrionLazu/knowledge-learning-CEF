<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Registration, e-mail activation and login, tested through real HTTP requests.
 */
#[TestDox('Inscription, activation par e-mail et connexion (pages web)')]
class RegistrationAndLoginTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    #[TestDox('Inscription par le formulaire : compte client créé, inactif, e-mail d\'activation envoyé')]
    public function testRegistrationForm(): void
    {
        $this->register('marie@test.fr', 'Guitare!2026ok');

        self::assertResponseRedirects('/login');
        self::assertEmailCount(1);
        $user = $this->users()->findOneByEmail('marie@test.fr');
        self::assertNotNull($user);
        self::assertContains(User::ROLE_CLIENT, $user->getRoles());
        self::assertFalse($user->isVerified());
    }

    #[TestDox('Inscription refusée si le mot de passe ne respecte pas les règles')]
    public function testRegistrationRejectsAWeakPassword(): void
    {
        $this->register('marie@test.fr', 'faible');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'au moins 12 caractères');
        self::assertNull($this->users()->findOneByEmail('marie@test.fr'));
    }

    #[TestDox('Inscription refusée si l\'adresse e-mail est déjà utilisée')]
    public function testRegistrationRejectsADuplicateEmail(): void
    {
        $this->register('marie@test.fr', 'Guitare!2026ok');
        $this->register('marie@test.fr', 'Guitare!2026ok');

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'Un compte existe déjà');
    }

    #[TestDox('Sécurité CSRF : un formulaire d\'inscription envoyé sans jeton valide est refusé')]
    public function testRegistrationRequiresACsrfToken(): void
    {
        $this->client->request('POST', '/register', ['registration_form' => [
            'firstName' => 'Marie', 'lastName' => 'Curie', 'email' => 'marie@test.fr',
            'plainPassword' => ['first' => 'Guitare!2026ok', 'second' => 'Guitare!2026ok'],
            '_token' => 'jeton-falsifie',
        ]]);

        self::assertResponseStatusCodeSame(422);
        self::assertNull($this->users()->findOneByEmail('marie@test.fr'));
    }

    #[TestDox('Le lien reçu par e-mail active le compte et connecte l\'utilisateur')]
    public function testActivationLinkVerifiesTheAccount(): void
    {
        $this->register('marie@test.fr', 'Guitare!2026ok');
        $link = $this->activationLink();

        $this->client->request('GET', $link);

        self::assertResponseRedirects('/');
        self::assertTrue($this->users()->findOneByEmail('marie@test.fr')->isVerified());
        $this->client->followRedirect();
        self::assertSelectorTextContains('main', 'Votre compte est activé');
        self::assertSelectorNotExists('.alert-warning');
    }

    #[TestDox('Sécurité : un lien d\'activation falsifié est refusé')]
    public function testForgedActivationLinkIsRejected(): void
    {
        $this->register('marie@test.fr', 'Guitare!2026ok');
        $forged = preg_replace('/signature=[^&]+/', 'signature=falsifiee', $this->activationLink());

        $this->client->request('GET', $forged);

        self::assertResponseRedirects('/login');
        self::assertFalse($this->users()->findOneByEmail('marie@test.fr')->isVerified());
    }

    #[TestDox('Connexion réussie avec l\'e-mail et le bon mot de passe')]
    public function testLoginWithValidCredentials(): void
    {
        $this->createUser('marie@test.fr', 'Guitare!2026ok');

        $this->login('marie@test.fr', 'Guitare!2026ok');

        self::assertResponseRedirects('/');
        $this->client->followRedirect();
        self::assertSelectorTextContains('nav', 'Déconnexion');
    }

    #[TestDox('Connexion refusée avec un mauvais mot de passe')]
    public function testLoginWithWrongPassword(): void
    {
        $this->createUser('marie@test.fr', 'Guitare!2026ok');

        $this->login('marie@test.fr', 'MauvaisMotDePasse!1');

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert-danger');
        self::assertSelectorTextNotContains('nav', 'Déconnexion');
    }

    #[TestDox('Sécurité CSRF : une connexion envoyée sans jeton est refusée')]
    public function testLoginRequiresACsrfToken(): void
    {
        $this->createUser('marie@test.fr', 'Guitare!2026ok');

        $this->client->request('POST', '/login', ['email' => 'marie@test.fr', 'password' => 'Guitare!2026ok']);
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-danger', 'CSRF');
        self::assertSelectorTextNotContains('nav', 'Déconnexion');
    }

    #[TestDox('Un compte non activé peut se connecter mais un message l\'invite à l\'activer')]
    public function testUnverifiedUserCanLogIn(): void
    {
        $this->createUser('marie@test.fr', 'Guitare!2026ok', verified: false);

        $this->login('marie@test.fr', 'Guitare!2026ok');
        $this->client->followRedirect();

        self::assertSelectorTextContains('.alert-warning', 'pas encore activé');
    }

    private function register(string $email, string $password): void
    {
        $this->client->request('GET', '/register');
        $this->client->submitForm('Créer mon compte', [
            'registration_form[firstName]' => 'Marie',
            'registration_form[lastName]' => 'Curie',
            'registration_form[email]' => $email,
            'registration_form[plainPassword][first]' => $password,
            'registration_form[plainPassword][second]' => $password,
        ]);
    }

    private function login(string $email, string $password): void
    {
        $this->client->request('GET', '/login');
        $this->client->submitForm('Se connecter', ['email' => $email, 'password' => $password]);
    }

    private function activationLink(): string
    {
        preg_match('/href="([^"]+\/verify\/email\?[^"]+)"/', (string) self::getMailerMessage()->getHtmlBody(), $matches);

        return html_entity_decode($matches[1]);
    }

    private function createUser(string $email, string $password, bool $verified = true): User
    {
        $container = static::getContainer();
        $user = (new User())->setEmail($email)->setFirstName('Marie')->setLastName('Curie')->setRoles([User::ROLE_CLIENT])->setIsVerified($verified);
        $user->setPassword($container->get(UserPasswordHasherInterface::class)->hashPassword($user, $password));
        $em = $container->get(EntityManagerInterface::class);
        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function users(): UserRepository
    {
        $repository = static::getContainer()->get(UserRepository::class);
        static::getContainer()->get(EntityManagerInterface::class)->clear();

        return $repository;
    }
}
