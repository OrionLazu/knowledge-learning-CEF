<?php

namespace App\Repository;

use App\Entity\Theme;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Data access component for {@see Theme} entities.
 *
 * @extends ServiceEntityRepository<Theme>
 */
class ThemeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Theme::class);
    }

    /**
     * Loads the whole catalogue (themes, their courses and their lessons) in a single SQL query,
     * instead of one query per theme and per course.
     *
     * @return list<Theme>
     */
    public function findCatalogue(): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.courses', 'c')->addSelect('c')
            ->leftJoin('c.lessons', 'l')->addSelect('l')
            ->orderBy('t.id', 'ASC')
            ->addOrderBy('c.id', 'ASC')
            ->addOrderBy('l.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
