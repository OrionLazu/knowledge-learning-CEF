<?php

namespace App\Tests\Repository;

use App\Entity\Purchase;
use App\Entity\PurchaseItem;
use App\Entity\User;
use App\Enum\PurchaseStatus;
use App\Repository\PurchaseItemRepository;
use App\Repository\PurchaseRepository;
use App\Tests\Support\DatabaseTestCase;
use PHPUnit\Framework\Attributes\TestDox;

/**
 * Data access components of the orders and order lines: functional and security tests.
 */
#[TestDox('Repositories des commandes')]
class PurchaseRepositoryTest extends DatabaseTestCase
{
    private PurchaseRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->service(PurchaseRepository::class);
    }

    #[TestDox('Une commande est retrouvée par sa session Stripe pour son propriétaire')]
    public function testFindByStripeSessionForTheOwner(): void
    {
        $user = $this->createUser();
        $purchase = $this->createPurchase($user, 'cs_test_owner');

        self::assertSame($purchase->getId(), $this->repository->findOneByStripeSessionForUser('cs_test_owner', $user)?->getId());
    }

    #[TestDox('Sécurité : la session Stripe d\'un autre client ne donne jamais accès à sa commande')]
    public function testStripeSessionOfAnotherClientIsRefused(): void
    {
        $owner = $this->createUser('owner@test.fr');
        $intruder = $this->createUser('intruder@test.fr');
        $this->createPurchase($owner, 'cs_test_owner');

        self::assertNull($this->repository->findOneByStripeSessionForUser('cs_test_owner', $intruder));
    }

    #[TestDox('Sécurité : une tentative d\'injection SQL dans l\'identifiant de session ne renvoie rien')]
    public function testSqlInjectionInStripeSession(): void
    {
        $user = $this->createUser();
        $this->createPurchase($user, 'cs_test_owner');

        self::assertNull($this->repository->findOneByStripeSessionForUser("' OR '1'='1", $user));
    }

    #[TestDox('Seules les commandes payées apparaissent dans l\'historique, avec leurs lignes')]
    public function testFindPaidForUser(): void
    {
        $user = $this->createUser();
        $paid = $this->createPurchase($user, 'cs_paid');
        $paid->markAsPaid(new \DateTimeImmutable());
        $this->createPurchase($user, 'cs_pending');
        $this->em->flush();
        $this->em->clear();

        $history = $this->repository->findPaidForUser($this->em->find(User::class, $user->getId()));

        self::assertCount(1, $history);
        self::assertSame(PurchaseStatus::Paid, $history[0]->getStatus());
        self::assertCount(1, $history[0]->getItems());
        self::assertCount(1, $this->repository->findPendingForUser($this->em->find(User::class, $user->getId())));
    }

    #[TestDox('La ligne de commande garde le libellé et le prix au moment de l\'achat')]
    public function testPurchaseItemKeepsLabelAndPrice(): void
    {
        $user = $this->createUser();
        $purchase = $this->createPurchase($user, 'cs_test');
        $lesson = $purchase->getItems()->first()->getLesson();
        $lesson->setPrice(9999)->setTitle('Nouveau titre');
        $this->em->flush();
        $this->em->clear();

        $item = $this->service(PurchaseItemRepository::class)->findOneBy([]);
        self::assertSame(2600, $item->getUnitPrice());
        self::assertStringContainsString('Leçon 1', $item->getLabel());
        self::assertSame(2600, $this->repository->find($purchase->getId())->getTotal());
    }

    private function createPurchase(User $user, string $stripeSessionId): Purchase
    {
        $theme = $this->createTheme('Thème '.$stripeSessionId);
        $purchase = new Purchase($user);
        $purchase->addItem(new PurchaseItem($this->lesson($theme, 1)));
        $purchase->setStripeSessionId($stripeSessionId);
        $this->em->persist($purchase);
        $this->em->flush();

        return $purchase;
    }
}
