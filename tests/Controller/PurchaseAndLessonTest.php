<?php

namespace App\Tests\Controller;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\LessonAccess;
use App\Entity\Theme;
use App\Entity\User;
use App\Repository\LessonAccessRepository;
use App\Tests\Support\FakeStripeHttpClient;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\TestDox;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Purchase, lesson pages, lesson validation and access control, tested through HTTP requests.
 * Stripe is simulated: no request leaves the machine.
 */
#[TestDox('Achat, leçons et contrôle d\'accès (pages web)')]
class PurchaseAndLessonTest extends WebTestCase
{
    private KernelBrowser $client;
    private FakeStripeHttpClient $stripe;
    private EntityManagerInterface $em;
    private Course $course;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->stripe = new FakeStripeHttpClient();
        ApiRequestor::setHttpClient($this->stripe);
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $theme = (new Theme())->setName('Jardinage');
        $this->course = (new Course())->setTitle('Cursus d\'initiation au jardinage')->setPrice(3000);
        $theme->addCourse($this->course);
        $this->em->persist($theme);
        $this->em->persist($this->course);
        foreach (['Les outils du jardinier', 'Jardiner avec la lune'] as $index => $title) {
            $lesson = (new Lesson())->setTitle($title)->setPosition($index + 1)->setPrice(1600)->setContent('Lorem ipsum');
            $this->course->addLesson($lesson);
            $this->em->persist($lesson);
        }
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
        parent::tearDown();
    }

    #[TestDox('Un visiteur voit le catalogue et le détail d\'un cursus')]
    public function testCatalogueIsPublic(): void
    {
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Cursus d\'initiation au jardinage');

        $this->client->request('GET', '/course/'.$this->course->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('main', 'Jardiner avec la lune');
        self::assertSelectorTextContains('main', '16,00 €');
    }

    #[TestDox('Achat test d\'une leçon : redirection vers Stripe, confirmation, puis accès à la leçon')]
    public function testBuyALessonThroughTheWebsite(): void
    {
        $user = $this->createAccount('client@test.fr');
        $this->client->loginUser($user);
        $lesson = $this->course->getLessons()->first();

        $crawler = $this->client->request('GET', '/course/'.$this->course->getId());
        $this->stripe->queueCheckoutSession('cs_test_web');
        $this->client->submit($crawler->filter('form[action="/purchase/lesson/'.$lesson->getId().'"]')->form());

        self::assertResponseStatusCodeSame(303);
        self::assertResponseRedirects('https://checkout.stripe.com/c/pay/cs_test_web');

        $this->stripe->queueCheckoutSession('cs_test_web', 'paid');
        $this->client->request('GET', '/purchase/success?session_id=cs_test_web');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Merci pour votre achat');

        $this->client->request('GET', '/lesson/'.$lesson->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Les outils du jardinier');
    }

    #[TestDox('Achat test d\'un cursus : les deux leçons deviennent accessibles')]
    public function testBuyACourseThroughTheWebsite(): void
    {
        $user = $this->createAccount('client@test.fr');
        $this->client->loginUser($user);

        $crawler = $this->client->request('GET', '/course/'.$this->course->getId());
        $this->stripe->queueCheckoutSession('cs_test_course');
        $this->client->submit($crawler->filter('form[action="/purchase/course/'.$this->course->getId().'"]')->form());
        $this->stripe->queueCheckoutSession('cs_test_course', 'paid');
        $this->client->request('GET', '/purchase/success?session_id=cs_test_course');

        self::assertCount(2, static::getContainer()->get(LessonAccessRepository::class)->findAccessibleLessonIds($user));
    }

    #[TestDox('Sécurité CSRF : un achat envoyé sans jeton valide est refusé')]
    public function testPurchaseRequiresACsrfToken(): void
    {
        $this->client->loginUser($this->createAccount('client@test.fr'));

        $this->client->request('POST', '/purchase/course/'.$this->course->getId(), ['_token' => 'jeton-falsifie']);

        self::assertResponseRedirects('/course/'.$this->course->getId());
        self::assertSame([], $this->stripe->requests, 'Stripe must not be called.');
    }

    #[TestDox('Un compte non activé est renvoyé au cursus sans paiement')]
    public function testUnverifiedUserCannotBuyThroughTheWebsite(): void
    {
        $this->client->loginUser($this->createAccount('inactif@test.fr', verified: false));
        $crawler = $this->client->request('GET', '/course/'.$this->course->getId());
        $form = $crawler->filter('form[action="/purchase/course/'.$this->course->getId().'"]')->form();

        $this->client->request('POST', $form->getUri(), $form->getPhpValues());

        self::assertResponseRedirects('/course/'.$this->course->getId());
        self::assertSame([], $this->stripe->requests);
    }

    #[TestDox('Un visiteur non connecté est envoyé vers la connexion pour ouvrir une leçon')]
    public function testAnonymousVisitorMustLogIn(): void
    {
        $this->client->request('GET', '/lesson/'.$this->course->getLessons()->first()->getId());

        self::assertResponseRedirects('/login');
    }

    #[TestDox('Un client qui n\'a pas acheté la leçon est renvoyé vers le cursus')]
    public function testClientWithoutPurchaseIsRedirected(): void
    {
        $this->client->loginUser($this->createAccount('client@test.fr'));

        $this->client->request('GET', '/lesson/'.$this->course->getLessons()->first()->getId());

        self::assertResponseRedirects('/course/'.$this->course->getId());
    }

    #[TestDox('Valider les deux leçons du cursus valide le cursus et délivre la certification')]
    public function testValidateLessonsUntilCertification(): void
    {
        $user = $this->createAccount('client@test.fr');
        foreach ($this->course->getLessons() as $lesson) {
            $this->em->persist(new LessonAccess($user, $lesson));
        }
        $this->em->flush();
        $this->client->loginUser($user);

        foreach ($this->course->getLessons() as $lesson) {
            $crawler = $this->client->request('GET', '/lesson/'.$lesson->getId());
            $this->client->submit($crawler->selectButton('Valider la leçon')->form());
        }

        self::assertResponseRedirects('/certifications');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.certificate', 'Jardinage');
        self::assertSelectorTextContains('main', 'le cursus « Cursus d\'initiation au jardinage » est validé');
    }

    #[TestDox('Sécurité : le back-office est refusé à un client et ouvert à un administrateur')]
    public function testBackOfficeIsReservedToAdministrators(): void
    {
        $this->client->loginUser($this->createAccount('client@test.fr'));
        $this->client->request('GET', '/admin');
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->createAccount('admin@test.fr', role: User::ROLE_ADMIN));
        $this->client->request('GET', '/admin');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Tableau de bord');
    }

    private function createAccount(string $email, bool $verified = true, string $role = User::ROLE_CLIENT): User
    {
        $user = (new User())->setEmail($email)->setFirstName('Jean')->setLastName('Test')->setRoles([$role])->setIsVerified($verified)->setPassword('unused');
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
