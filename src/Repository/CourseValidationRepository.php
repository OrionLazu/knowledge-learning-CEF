<?php

namespace App\Repository;

use App\Entity\Course;
use App\Entity\CourseValidation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Data access component for the validated courses ({@see CourseValidation}).
 *
 * @extends ServiceEntityRepository<CourseValidation>
 */
class CourseValidationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourseValidation::class);
    }

    public function isValidated(User $user, Course $course): bool
    {
        return null !== $this->findOneBy(['user' => $user, 'course' => $course]);
    }

    /**
     * Identifiers of the courses validated by the user.
     *
     * @return list<int>
     */
    public function findValidatedCourseIds(User $user): array
    {
        $rows = $this->createQueryBuilder('v')
            ->select('IDENTITY(v.course) AS courseId')
            ->andWhere('v.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map('intval', $rows);
    }
}
