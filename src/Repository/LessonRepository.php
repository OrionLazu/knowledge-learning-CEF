<?php

namespace App\Repository;

use App\Entity\Lesson;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Data access component for {@see Lesson} entities.
 *
 * @extends ServiceEntityRepository<Lesson>
 */
class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lesson::class);
    }

    /**
     * Loads a lesson together with its course and theme (used by the lesson page).
     */
    public function findWithCourse(int $id): ?Lesson
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.course', 'c')->addSelect('c')
            ->innerJoin('c.theme', 't')->addSelect('t')
            ->andWhere('l.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
