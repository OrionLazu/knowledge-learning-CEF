<?php

namespace App\Repository;

use App\Entity\Certification;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Data access component for the certifications ({@see Certification}).
 *
 * @extends ServiceEntityRepository<Certification>
 */
class CertificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Certification::class);
    }

    public function hasCertification(User $user, Theme $theme): bool
    {
        return null !== $this->findOneBy(['user' => $user, 'theme' => $theme]);
    }

    /**
     * Certifications of the user, most recent first, with their theme.
     *
     * @return list<Certification>
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.theme', 't')->addSelect('t')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.obtainedAt', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
