<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Contract\PurchasableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Training course ("cursus"), for example "Cursus d'initiation à la guitare".
 *
 * A course belongs to a theme and is made of several lessons. Buying a course
 * gives access to all of its lessons.
 */
#[ORM\Entity(repositoryClass: CourseRepository::class)]
class Course implements AuditableInterface, PurchasableInterface
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

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * Price in euro cents (5000 = 50,00 €): integers avoid floating-point rounding errors.
     */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $price = 0;

    #[ORM\ManyToOne(inversedBy: 'courses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Theme $theme = null;

    /**
     * @var Collection<int, Lesson>
     */
    #[ORM\OneToMany(targetEntity: Lesson::class, mappedBy: 'course', orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $lessons;

    public function __construct()
    {
        $this->lessons = new ArrayCollection();
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getTheme(): ?Theme
    {
        return $this->theme;
    }

    public function setTheme(?Theme $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): static
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setCourse($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): static
    {
        $this->lessons->removeElement($lesson);

        return $this;
    }

    /**
     * Lessons unlocked by buying this course: all of them.
     *
     * @return list<Lesson>
     */
    public function getIncludedLessons(): array
    {
        return array_values($this->lessons->toArray());
    }

    public function getPurchaseLabel(): string
    {
        return sprintf('%s (cursus complet)', $this->title);
    }

    public function __toString(): string
    {
        return (string) $this->title;
    }
}
