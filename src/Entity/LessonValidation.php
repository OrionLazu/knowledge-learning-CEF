<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\LessonValidationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * A lesson marked as completed by a client (button "Valider la leçon").
 */
#[ORM\Entity(repositoryClass: LessonValidationRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_lesson_validation_user_lesson', columns: ['user_id', 'lesson_id'])]
class LessonValidation implements AuditableInterface
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
    private Lesson $lesson;

    #[ORM\Column]
    private \DateTimeImmutable $validatedAt;

    public function __construct(User $user, Lesson $lesson, \DateTimeImmutable $validatedAt)
    {
        $this->user = $user;
        $this->lesson = $lesson;
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

    public function getLesson(): Lesson
    {
        return $this->lesson;
    }

    public function getValidatedAt(): \DateTimeImmutable
    {
        return $this->validatedAt;
    }
}
