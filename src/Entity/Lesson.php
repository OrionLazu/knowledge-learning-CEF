<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Contract\PurchasableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\LessonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Lesson of a course: a text sheet with a video. It can be bought on its own.
 */
#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson implements AuditableInterface, PurchasableInterface
{
    use AuditableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 150)]
    private ?string $title = null;

    /**
     * Order of the lesson inside its course (1, 2, ...).
     */
    #[ORM\Column]
    #[Assert\Positive]
    private int $position = 1;

    /**
     * Text of the lesson sheet.
     */
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(requireTld: true)]
    #[Assert\Length(max: 255)]
    private ?string $videoUrl = null;

    /**
     * Price in euro cents.
     */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $price = 0;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Course $course = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getVideoUrl(): ?string
    {
        return $this->videoUrl;
    }

    public function setVideoUrl(?string $videoUrl): static
    {
        $this->videoUrl = $videoUrl;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;

        return $this;
    }

    /**
     * Buying a lesson only unlocks the lesson itself.
     *
     * @return list<Lesson>
     */
    public function getIncludedLessons(): array
    {
        return [$this];
    }

    public function getPurchaseLabel(): string
    {
        return sprintf('%s - Leçon n°%d : %s', $this->course?->getTitle(), $this->position, $this->title);
    }

    public function __toString(): string
    {
        return sprintf('%s - Leçon n°%d : %s', $this->course?->getTitle(), $this->position, $this->title);
    }
}
