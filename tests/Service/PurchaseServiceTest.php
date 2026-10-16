<?php

namespace App\Tests\Service;

use App\Enum\PurchaseStatus;
use App\Exception\PurchaseException;
use App\Repository\LessonAccessRepository;
use App\Service\PurchaseService;
use App\Tests\Support\DatabaseTestCase;
use App\Tests\Support\FakeStripeHttpClient;
use PHPUnit\Framework\Attributes\TestDox;
use Stripe\ApiRequestor;
use Stripe\HttpClient\CurlClient;

/**
 * Purchase of a course or a lesson, with a simulated Stripe API (no network call).
 */
#[TestDox('Achat d\'un cursus ou d\'une leçon')]
class PurchaseServiceTest extends DatabaseTestCase
{
    private FakeStripeHttpClient $stripe;
    private PurchaseService $purchaseService;
    private LessonAccessRepository $accessRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripe = new FakeStripeHttpClient();
        ApiRequestor::setHttpClient($this->stripe);
        $this->purchaseService = $this->service(PurchaseService::class);
        $this->accessRepository = $this->service(LessonAccessRepository::class);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(CurlClient::instance());
        parent::tearDown();
    }

    #[TestDox('Achat complet d\'une leçon : commande, paiement Stripe, accès à la leçon seulement')]
    public function testBuyALesson(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $lesson = $this->lesson($theme, 1);

        $purchase = $this->purchaseService->createPurchase($user, $lesson);
        self::assertSame(PurchaseStatus::Pending, $purchase->getStatus());
        self::assertSame(2600, $purchase->getTotal());

        $this->stripe->queueCheckoutSession('cs_test_lesson');
        $url = $this->purchaseService->startPayment($purchase, 'https://kl.test/success', 'https://kl.test/cancel');
        self::assertSame('https://checkout.stripe.com/c/pay/cs_test_lesson', $url);
        self::assertSame(2600, $this->stripe->requests[0]['params']['line_items'][0]['price_data']['unit_amount']);
        self::assertSame('eur', $this->stripe->requests[0]['params']['line_items'][0]['price_data']['currency']);

        $this->stripe->queueCheckoutSession('cs_test_lesson', 'paid');
        $confirmed = $this->purchaseService->confirmPayment('cs_test_lesson', $user);

        self::assertTrue($confirmed->isPaid());
        self::assertNotNull($confirmed->getPaidAt());
        self::assertTrue($this->accessRepository->hasAccess($user, $lesson));
        self::assertFalse($this->accessRepository->hasAccess($user, $this->lesson($theme, 2)));
    }

    #[TestDox('Achat d\'un cursus : toutes les leçons du cursus sont débloquées')]
    public function testBuyACourseUnlocksAllItsLessons(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();

        $this->pay($user, $this->firstCourse($theme), 'cs_test_course');

        self::assertCount(2, $this->accessRepository->findAccessibleLessonIds($user));
        self::assertSame(5000, $this->stripe->requests[0]['params']['line_items'][0]['price_data']['unit_amount']);
    }

    #[TestDox('Un compte non activé ne peut pas acheter')]
    public function testUnverifiedUserCannotBuy(): void
    {
        $user = $this->createUser('inactif@test.fr', false);
        $theme = $this->createTheme();

        $this->expectException(PurchaseException::class);
        $this->expectExceptionMessage('Activez votre compte');
        $this->purchaseService->createPurchase($user, $this->lesson($theme, 1));
    }

    #[TestDox('Une leçon déjà achetée ne peut pas être achetée de nouveau')]
    public function testOwnedLessonCannotBeBoughtAgain(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $this->grantAccess($user, $this->lesson($theme, 1));

        $this->expectException(PurchaseException::class);
        $this->purchaseService->createPurchase($user, $this->lesson($theme, 1));
    }

    #[TestDox('Un cursus dont une seule leçon est possédée reste achetable et débloque la leçon manquante')]
    public function testCourseCanBeBoughtWhenOneLessonIsOwned(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $this->grantAccess($user, $this->lesson($theme, 1));

        $this->pay($user, $this->firstCourse($theme), 'cs_test_course');

        self::assertCount(2, $this->accessRepository->findAccessibleLessonIds($user));
    }

    #[TestDox('Sécurité : sans confirmation de Stripe, la commande reste impayée et rien n\'est débloqué')]
    public function testUnpaidSessionGrantsNothing(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $purchase = $this->purchaseService->createPurchase($user, $this->lesson($theme, 1));
        $this->stripe->queueCheckoutSession('cs_test_unpaid');
        $this->purchaseService->startPayment($purchase, 'https://kl.test/success', 'https://kl.test/cancel');

        $this->stripe->queueCheckoutSession('cs_test_unpaid', 'unpaid');
        try {
            $this->purchaseService->confirmPayment('cs_test_unpaid', $user);
            self::fail('An unpaid session must be refused.');
        } catch (PurchaseException) {
        }

        self::assertTrue($purchase->isPending());
        self::assertSame([], $this->accessRepository->findAccessibleLessonIds($user));
    }

    #[TestDox('Sécurité : un client ne peut pas valider la commande d\'un autre client')]
    public function testAnotherUserCannotConfirmThePurchase(): void
    {
        $owner = $this->createUser('owner@test.fr');
        $intruder = $this->createUser('intruder@test.fr');
        $theme = $this->createTheme();
        $purchase = $this->purchaseService->createPurchase($owner, $this->lesson($theme, 1));
        $this->stripe->queueCheckoutSession('cs_test_owner');
        $this->purchaseService->startPayment($purchase, 'https://kl.test/success', 'https://kl.test/cancel');

        $this->expectException(PurchaseException::class);
        $this->purchaseService->confirmPayment('cs_test_owner', $intruder);
    }

    #[TestDox('Recharger la page de confirmation ne débloque rien deux fois et ne rappelle pas Stripe')]
    public function testConfirmationIsIdempotent(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $this->pay($user, $this->firstCourse($theme), 'cs_test_course');
        $requestCount = \count($this->stripe->requests);

        $this->purchaseService->confirmPayment('cs_test_course', $user);

        self::assertCount($requestCount, $this->stripe->requests);
        self::assertCount(2, $this->accessRepository->findAccessibleLessonIds($user));
    }

    #[TestDox('Une nouvelle commande annule la précédente restée en attente de paiement')]
    public function testNewPurchaseCancelsThePendingOne(): void
    {
        $user = $this->createUser();
        $theme = $this->createTheme();
        $first = $this->purchaseService->createPurchase($user, $this->lesson($theme, 1));

        $this->purchaseService->createPurchase($user, $this->lesson($theme, 2));

        self::assertSame(PurchaseStatus::Canceled, $first->getStatus());
    }

    /**
     * Runs a whole purchase with a payment accepted by the fake Stripe API.
     */
    private function pay(\App\Entity\User $user, \App\Entity\Contract\PurchasableInterface $product, string $sessionId): void
    {
        $purchase = $this->purchaseService->createPurchase($user, $product);
        $this->stripe->queueCheckoutSession($sessionId);
        $this->purchaseService->startPayment($purchase, 'https://kl.test/success', 'https://kl.test/cancel');
        $this->stripe->queueCheckoutSession($sessionId, 'paid');
        $this->purchaseService->confirmPayment($sessionId, $user);
    }
}
