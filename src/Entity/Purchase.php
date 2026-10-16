<?php

namespace App\Entity;

use App\Entity\Contract\AuditableInterface;
use App\Entity\Trait\AuditableTrait;
use App\Enum\PurchaseStatus;
use App\Repository\PurchaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Order placed by a client. It is paid through Stripe Checkout (test mode).
 *
 * The total is computed from the order lines when they are added, and kept in the table so that
 * a later change of the catalogue prices never modifies an order already placed.
 */
#[ORM\Entity(repositoryClass: PurchaseRepository::class)]
#[ORM\Index(name: 'idx_purchase_status', columns: ['status'])]
class Purchase implements AuditableInterface
{
    use AuditableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $customer;

    #[ORM\Column(length: 20, enumType: PurchaseStatus::class)]
    private PurchaseStatus $status = PurchaseStatus::Pending;

    /**
     * Total amount in euro cents.
     */
    #[ORM\Column]
    private int $total = 0;

    /**
     * Identifier of the Stripe Checkout session used to pay this order.
     */
    #[ORM\Column(length: 255, unique: true, nullable: true)]
    private ?string $stripeSessionId = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    /**
     * @var Collection<int, PurchaseItem>
     */
    #[ORM\OneToMany(targetEntity: PurchaseItem::class, mappedBy: 'purchase', cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    public function __construct(User $customer)
    {
        $this->customer = $customer;
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomer(): User
    {
        return $this->customer;
    }

    public function getStatus(): PurchaseStatus
    {
        return $this->status;
    }

    public function isPending(): bool
    {
        return PurchaseStatus::Pending === $this->status;
    }

    public function isPaid(): bool
    {
        return PurchaseStatus::Paid === $this->status;
    }

    public function markAsPaid(\DateTimeImmutable $paidAt): static
    {
        $this->status = PurchaseStatus::Paid;
        $this->paidAt = $paidAt;

        return $this;
    }

    public function markAsCanceled(): static
    {
        $this->status = PurchaseStatus::Canceled;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): static
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getPaidAt(): ?\DateTimeImmutable
    {
        return $this->paidAt;
    }

    /**
     * @return Collection<int, PurchaseItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /**
     * Adds an order line and updates the total.
     */
    public function addItem(PurchaseItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setPurchase($this);
            $this->total += $item->getUnitPrice();
        }

        return $this;
    }

    public function __toString(): string
    {
        return sprintf('Commande n°%d', $this->id);
    }
}
