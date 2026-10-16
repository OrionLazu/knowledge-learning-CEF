<?php

namespace App\Service;

use App\Entity\Contract\PurchasableInterface;
use App\Entity\LessonAccess;
use App\Entity\Purchase;
use App\Entity\PurchaseItem;
use App\Entity\User;
use App\Exception\PurchaseException;
use App\Repository\LessonAccessRepository;
use App\Repository\PurchaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;

/**
 * Purchase of a course or a lesson: order -> Stripe payment -> access to the lessons.
 *
 * Business rules:
 * - only a client whose account was activated by e-mail can buy;
 * - a lesson the client can already open cannot be bought again;
 * - a course is refused only if the client can already open every one of its lessons;
 * - the access rights are granted only once Stripe has confirmed the payment.
 */
class PurchaseService
{
    public function __construct(
        private readonly StripeService $stripe,
        private readonly EntityManagerInterface $entityManager,
        private readonly PurchaseRepository $purchaseRepository,
        private readonly LessonAccessRepository $lessonAccessRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    /**
     * Checks that the client is allowed to buy the product.
     *
     * @throws PurchaseException with a message that can be shown to the client
     */
    public function assertCanBuy(User $customer, PurchasableInterface $product): void
    {
        if (!$customer->isVerified()) {
            throw new PurchaseException('Activez votre compte grâce au lien reçu par e-mail avant d\'effectuer un achat.');
        }

        $alreadyOwned = $this->lessonAccessRepository->findAccessibleLessonIds($customer);
        $missing = array_filter(
            $product->getIncludedLessons(),
            static fn ($lesson) => !\in_array($lesson->getId(), $alreadyOwned, true),
        );
        if ([] === $missing) {
            throw new PurchaseException('Vous avez déjà accès à ce contenu.');
        }
    }

    /**
     * Creates a pending order for the product.
     *
     * Previous orders the client left unpaid are canceled, so a client has at most one pending order.
     *
     * @throws PurchaseException if the client is not allowed to buy the product
     */
    public function createPurchase(User $customer, PurchasableInterface $product): Purchase
    {
        $this->assertCanBuy($customer, $product);

        foreach ($this->purchaseRepository->findPendingForUser($customer) as $previous) {
            $previous->markAsCanceled();
        }

        $purchase = new Purchase($customer);
        $purchase->addItem(new PurchaseItem($product));

        $this->entityManager->persist($purchase);
        $this->entityManager->flush();

        return $purchase;
    }

    /**
     * Creates the Stripe payment page and returns the URL the client must be redirected to.
     */
    public function startPayment(Purchase $purchase, string $successUrl, string $cancelUrl): string
    {
        $session = $this->stripe->createCheckoutSession($purchase, $successUrl, $cancelUrl);

        $purchase->setStripeSessionId($session->id);
        $this->entityManager->flush();

        return (string) $session->url;
    }

    /**
     * Confirms the order when the client comes back from Stripe, then unlocks the lessons.
     *
     * The operation is idempotent: reloading the confirmation page does not grant anything twice.
     *
     * @throws PurchaseException if the order is unknown, belongs to someone else or is not paid
     */
    public function confirmPayment(string $stripeSessionId, User $customer): Purchase
    {
        $purchase = $this->purchaseRepository->findOneByStripeSessionForUser($stripeSessionId, $customer);
        if (null === $purchase) {
            throw new PurchaseException('Commande introuvable.');
        }

        if ($purchase->isPaid()) {
            return $purchase;
        }

        if (!$this->stripe->isCheckoutSessionPaid($stripeSessionId)) {
            throw new PurchaseException('Le paiement n\'a pas été confirmé par Stripe.');
        }

        $purchase->markAsPaid($this->clock->now());
        $this->grantAccess($purchase);
        $this->entityManager->flush();

        return $purchase;
    }

    /**
     * Cancels a pending order (payment abandoned, or Stripe unavailable).
     */
    public function cancel(Purchase $purchase): void
    {
        if ($purchase->isPending()) {
            $purchase->markAsCanceled();
            $this->entityManager->flush();
        }
    }

    /**
     * Creates one access row per lesson included in the order, skipping the lessons already owned.
     */
    private function grantAccess(Purchase $purchase): void
    {
        $customer = $purchase->getCustomer();
        $owned = $this->lessonAccessRepository->findAccessibleLessonIds($customer);

        foreach ($purchase->getItems() as $item) {
            foreach ($item->getProduct()?->getIncludedLessons() ?? [] as $lesson) {
                if (\in_array($lesson->getId(), $owned, true)) {
                    continue;
                }
                $this->entityManager->persist(new LessonAccess($customer, $lesson, $purchase));
                $owned[] = $lesson->getId();
            }
        }
    }
}
