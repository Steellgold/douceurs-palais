<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order {
  const STATUS_PENDING = 'pending';
  const STATUS_PAYMENT_PROCESSING = 'payment_processing';
  const STATUS_PAID = 'paid';
  const STATUS_PREPARING = 'preparing';
  const STATUS_SHIPPED = 'shipped';
  const STATUS_DELIVERED = 'delivered';
  const STATUS_CANCELLED = 'cancelled';
  const STATUS_REFUNDED = 'refunded';

  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  #[ORM\Column(length: 255)]
  private ?string $reference = null;

  #[ORM\ManyToOne(inversedBy: 'orders')]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $user = null;

  #[ORM\Column]
  private ?float $totalAmount = 0;

  #[ORM\Column(length: 50)]
  private ?string $status = self::STATUS_PENDING;

  #[ORM\Column(nullable: true)]
  private ?string $stripeSessionId = null;

  #[ORM\Column(nullable: true)]
  private ?string $stripePaymentIntentId = null;

  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  #[ORM\Column(type: 'json', nullable: true)]
  private array $shippingAddress = [];

  #[ORM\Column(type: 'json', nullable: true)]
  private array $billingAddress = [];

  #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
  private Collection $items;

  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->reference = $this->generateReference();
    $this->items = new ArrayCollection();
  }

  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  private function generateReference(): string {
    return 'ORD-' . strtoupper(substr(uniqid('', true), 0, 8));
  }

  public function getId(): ?string {
    return $this->id;
  }

  public function getReference(): ?string {
    return $this->reference;
  }

  public function setReference(string $reference): static {
    $this->reference = $reference;

    return $this;
  }

  public function getUser(): ?User {
    return $this->user;
  }

  public function setUser(?User $user): static {
    $this->user = $user;

    return $this;
  }

  public function getTotalAmount(): ?float {
    return $this->totalAmount;
  }

  public function setTotalAmount(float $totalAmount): static {
    $this->totalAmount = $totalAmount;

    return $this;
  }

  public function getStatus(): ?string {
    return $this->status;
  }

  public function setStatus(string $status): static {
    $this->status = $status;

    return $this;
  }

  public function getStripeSessionId(): ?string {
    return $this->stripeSessionId;
  }

  public function setStripeSessionId(?string $stripeSessionId): static {
    $this->stripeSessionId = $stripeSessionId;

    return $this;
  }

  public function getStripePaymentIntentId(): ?string {
    return $this->stripePaymentIntentId;
  }

  public function setStripePaymentIntentId(?string $stripePaymentIntentId): static {
    $this->stripePaymentIntentId = $stripePaymentIntentId;

    return $this;
  }

  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  public function getShippingAddress(): array {
    return $this->shippingAddress;
  }

  public function setShippingAddress(array $shippingAddress): static {
    $this->shippingAddress = $shippingAddress;

    return $this;
  }

  public function getBillingAddress(): array {
    return $this->billingAddress;
  }

  public function setBillingAddress(array $billingAddress): static {
    $this->billingAddress = $billingAddress;

    return $this;
  }

  /**
   * @return Collection<int, OrderItem>
   */
  public function getItems(): Collection {
    return $this->items;
  }

  public function addItem(OrderItem $item): static {
    if (!$this->items->contains($item)) {
      $this->items->add($item);
      $item->setOrder($this);
    }

    return $this;
  }

  public function removeItem(OrderItem $item): static {
    if ($this->items->removeElement($item)) {
      if ($item->getOrder() === $this) {
        $item->setOrder(null);
      }
    }

    return $this;
  }

  public function getItemsByBakery(): array {
    $itemsByBakery = [];

    foreach ($this->items as $item) {
      $bakery = $item->getProduct()->getBakery();
      $bakeryId = $bakery->getId();

      if (!isset($itemsByBakery[$bakeryId])) {
        $itemsByBakery[$bakeryId] = [
          'bakery' => $bakery,
          'items' => [],
          'total' => 0
        ];
      }

      $itemsByBakery[$bakeryId]['items'][] = $item;
      $itemsByBakery[$bakeryId]['total'] += $item->getPrice() * $item->getQuantity();
    }

    return $itemsByBakery;
  }

  public function getStatusLabel(): string {
    return match ($this->status) {
      self::STATUS_PENDING => 'En attente',
      self::STATUS_PAYMENT_PROCESSING => 'Paiement en cours',
      self::STATUS_PAID => 'Payée',
      self::STATUS_PREPARING => 'En préparation',
      self::STATUS_SHIPPED => 'Expédiée',
      self::STATUS_DELIVERED => 'Livrée',
      self::STATUS_CANCELLED => 'Annulée',
      self::STATUS_REFUNDED => 'Remboursée',
      default => 'Statut inconnu',
    };
  }

  public function getStatusClass(): string {
    return match ($this->status) {
      self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
      self::STATUS_PAYMENT_PROCESSING => 'bg-blue-100 text-blue-800',
      self::STATUS_PAID, self::STATUS_DELIVERED => 'bg-green-100 text-green-800',
      self::STATUS_PREPARING => 'bg-indigo-100 text-indigo-800',
      self::STATUS_SHIPPED => 'bg-purple-900 text-purple-800',
      self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
      default => 'bg-gray-100 text-gray-800',
    };
  }
}