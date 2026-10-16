<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\Lesson;
use App\Entity\LessonValidation;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Data access component for the validated lessons ({@see LessonValidation}).
 *
 * @extends ServiceEntityRepository<LessonValidation>
 */
class LessonValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LessonValidation::class);
    }

    public function isValidated(User $user, Lesson $lesson): bool
    {
        return null !== $this->findOneBy(['user' => $user, 'lesson' => $lesson]);
    }

    /**
     * Identifiers of the lessons validated by the user.
     *
     * @return list<int>
     */
    public function findValidatedLessonIds(User $user): array
    {
        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.lesson) AS lessonId')
            ->andWhere('v.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $rows);
    }

    /**
     * Number of lessons of the course validated by the user.
     */
    public function countValidatedInCourse(User $user, Course $course): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.lesson', 'l')
            ->andWhere('v.user = :user')
            ->andWhere('l.course = :course')
            ->setParameter('user', $user)
            ->setParameter('course', $course)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Number of lessons of the theme (all courses together) validated by the user.
     */
    public function countValidatedInTheme(User $user, Theme $theme): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->innerJoin('v.lesson', 'l')
            ->innerJoin('l.course', 'c')
            ->andWhere('v.user = :user')
            ->andWhere('c.theme = :theme')
            ->setParameter('user', $user)
            ->setParameter('theme', $theme)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
