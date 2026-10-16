<?php

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\LessonAccess;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Data access component for the lesson access rights ({@see LessonAccess}).
 *
 * Every query is filtered on the user given as argument, so one client can never
 * read the rights of another client.
 *
 * @extends ServiceEntityRepository<LessonAccess>
 */
class LessonAccessRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonAccess::class);
    }

    /**
     * Tells whether the user bought the lesson (alone or through its course).
     */
    public function hasAccess(User $user, Lesson $lesson): bool
    {
        return null !== $this->createQueryBuilder('a')
            ->select('a.id')
            ->andWhere('a.user = :user')
            ->andWhere('a.lesson = :lesson')
            ->setParameter('user', $user)
            ->setParameter('lesson', $lesson)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Identifiers of all the lessons the user can open.
     *
     * @return list<int>
     */
    public function findAccessibleLessonIds(User $user): array
    {
        $rows = $this->createQueryBuilder('a')
            ->select('IDENTITY(a.lesson) AS lessonId')
            ->andWhere('a.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $rows);
    }
}
