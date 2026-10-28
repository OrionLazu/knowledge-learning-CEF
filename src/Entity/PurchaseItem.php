<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Contract\PurchasableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Repository\PurchaseItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Line of an order: one course OR one lesson.
 *
 * The label and the price are copied at the time of the purchase, so the order history stays
 * correct even if the course or the lesson is later renamed, repriced or deleted.
 */
#[ORM\Entity(repositoryClass: PurchaseItemRepository::class)]
class PurchaseItem implements AuditableInterface
{
    use AuditableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Purchase $purchase = null;

    /**
     * Bought course (null when the line is a single lesson).
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Course $course = null;

    /**
     * Bought lesson (null when the line is a whole course).
     */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Lesson $lesson = null;

    #[ORM\Column(length: 255)]
    private string $label;

    /**
     * Price paid in euro cents.
     */
    #[ORM\Column]
    private int $unitPrice;

    public function __construct(PurchasableInterface $product)
    {
        if ($product instanceof Course) {
            $this->course = $product;
        } elseif ($product instanceof Lesson) {
            $this->lesson = $product;
        } else {
            throw new \InvalidArgumentException('Only a course or a lesson can be bought.');
        }

        $this->label = $product->getPurchaseLabel();
        $this->unitPrice = $product->getPrice();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPurchase(): ?Purchase
    {
        return $this->purchase;
    }

    public function setPurchase(Purchase $purchase): static
    {
        $this->purchase = $purchase;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    /**
     * The bought course or lesson, or null if it was deleted from the catalogue since.
     */
    public function getProduct(): ?PurchasableInterface
    {
        return $this->course ?? $this->lesson;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function __toString(): string
    {
        return $this->label;
    }
}
