<?php

namespace App\Repository;

use App\Entity\PurchaseItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Data access component for {@see PurchaseItem} entities.
 *
 * Only the generic methods inherited from Doctrine (find, findBy, findOneBy, findAll) are needed.
 *
 * @extends ServiceEntityRepository<PurchaseItem>
 */
class PurchaseItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PurchaseItem::class);
    }
}
