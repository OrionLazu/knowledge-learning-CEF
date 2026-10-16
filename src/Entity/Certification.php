<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\CertificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * "Knowledge Learning" certification earned by a client for a theme.
 *
 * It is created automatically when the client has validated every lesson of the theme.
 */
#[ORM\Entity(repositoryClass: CertificationRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_certification_user_theme', columns: ['user_id', 'theme_id'])]
class Certification implements AuditableInterface
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
    private Theme $theme;

    #[ORM\Column]
    private \DateTimeImmutable $obtainedAt;

    public function __construct(User $user, Theme $theme, \DateTimeImmutable $obtainedAt)
    {
        $this->user = $user;
        $this->theme = $theme;
        $this->obtainedAt = $obtainedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    public function getObtainedAt(): \DateTimeImmutable
    {
        return $this->obtainedAt;
    }
}
