<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\LessonAccessRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Right for a client to open the page of a lesson.
 *
 * One row is created per unlocked lesson when a purchase is paid: buying a course creates
 * one row for each of its lessons. The unique constraint prevents granting the same lesson twice.
 */
#[ORM\Entity(repositoryClass: LessonAccessRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_lesson_access_user_lesson', columns: ['user_id', 'lesson_id'])]
class LessonAccess implements AuditableInterface
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

    /**
     * Purchase that granted the access (null if granted manually by an administrator).
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Purchase $purchase;

    public function __construct(User $user, Lesson $lesson, ?Purchase $purchase = null)
    {
        $this->user = $user;
        $this->lesson = $lesson;
        $this->purchase = $purchase;
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

    public function getPurchase(): ?Purchase
    {
        return $this->purchase;
    }
}
