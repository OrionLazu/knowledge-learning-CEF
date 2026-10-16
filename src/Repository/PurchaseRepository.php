<?php

namespace App\Repository;

use App\Entity\Purchase;
use App\Entity\User;
use App\Enum\PurchaseStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Data access component for the orders ({@see Purchase}).
 *
 * @extends ServiceEntityRepository<Purchase>
 */
class PurchaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Purchase::class);
    }

    /**
     * Finds the order paid through a Stripe Checkout session, only if it belongs to the given user.
     *
     * Filtering on the customer prevents a user from confirming or viewing another client's order
     * by reusing their Stripe session identifier.
     */
    public function findOneByStripeSessionForUser(string $sessionId, User $customer): ?Purchase
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.stripeSessionId = :sessionId')
            ->andWhere('p.customer = :customer')
            ->setParameter('sessionId', $sessionId)
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Orders of the user still waiting for their payment.
     *
     * @return list<Purchase>
     */
    public function findPendingForUser(User $customer): array
    {
        return $this->findBy(['customer' => $customer, 'status' => PurchaseStatus::Pending]);
    }

    /**
     * Paid orders of the user with their lines, most recent first.
     *
     * @return list<Purchase>
     */
    public function findPaidForUser(User $customer): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.items', 'i')->addSelect('i')
            ->andWhere('p.customer = :customer')
            ->andWhere('p.status = :status')
            ->setParameter('customer', $customer)
            ->setParameter('status', PurchaseStatus::Paid)
            ->orderBy('p.paidAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
