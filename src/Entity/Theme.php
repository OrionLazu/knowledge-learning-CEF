<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\ThemeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Training theme (Music, IT, Gardening, Cooking...): the highest level of the catalogue.
 *
 * A theme groups one or more courses. Validating every lesson of a theme earns
 * the "Knowledge Learning" certification for that theme.
 */
#[ORM\Entity(repositoryClass: ThemeRepository::class)]
#[UniqueEntity(fields: ['name'], message: 'Ce thème existe déjà.')]
class Theme implements AuditableInterface
{
    use AuditableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, Course>
     */
    #[ORM\OneToMany(targetEntity: Course::class, mappedBy: 'theme', orphanRemoval: true)]
    #[ORM\OrderBy(['id' => 'ASC'])]
    private Collection $courses;

    public function __construct()
    {
        $this->courses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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

    /**
     * @return Collection<int, Course>
     */
    public function getCourses(): Collection
    {
        return $this->courses;
    }

    public function addCourse(Course $course): static
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            $course->setTheme($this);
        }

        return $this;
    }

    public function removeCourse(Course $course): static
    {
        $this->courses->removeElement($course);

        return $this;
    }

    /**
     * All the lessons of the theme, across its courses.
     *
     * @return list<Lesson>
     */
    public function getLessons(): array
    {
        $lessons = [];
        foreach ($this->courses as $course) {
            array_push($lessons, ...$course->getLessons()->toArray());
        }

        return $lessons;
    }

    public function __toString(): string
    {
        return (string) $this->name;
    }
}
