<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\CourseValidationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A course completed by a client.
 *
 * It is never created by hand: it is created automatically as soon as the client
 * has validated every lesson of the course.
 */
#[ORM\Entity(repositoryClass: CourseValidationRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_course_validation_user_course', columns: ['user_id', 'course_id'])]
class CourseValidation implements AuditableInterface
{
    use AuditableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Course $course;

    #[ORM\Column]
    private \DateTimeImmutable $validatedAt;

    public function __construct(User $user, Course $course, \DateTimeImmutable $validatedAt)
    {
        $this->user = $user;
        $this->course = $course;
        $this->validatedAt = $validatedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function getValidatedAt(): \DateTimeImmutable
    {
        return $this->validatedAt;
    }
}
