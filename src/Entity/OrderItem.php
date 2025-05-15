<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OrderItem {
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  #[ORM\ManyToOne(inversedBy: 'items')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Order $order = null;

  #[ORM\ManyToOne]
  #[ORM\JoinColumn(nullable: false)]
  private ?Product $product = null;

  #[ORM\Column]
  private ?int $quantity = 1;

  #[ORM\Column]
  private ?float $price = 0;

  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  #[ORM\Column(options: ['default' => false])]
  private bool $redeemedWithPoints = false;

  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
  }

  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  public function getId(): ?string {
    return $this->id;
  }

  public function getOrder(): ?Order {
    return $this->order;
  }

  public function setOrder(?Order $order): static {
    $this->order = $order;

    return $this;
  }

  public function getProduct(): ?Product {
    return $this->product;
  }

  public function setProduct(?Product $product): static {
    $this->product = $product;
    if ($product) {
      $this->price = $product->getPrice();
    }

    return $this;
  }

  public function getQuantity(): ?int {
    return $this->quantity;
  }

  public function setQuantity(int $quantity): static {
    $this->quantity = $quantity;

    return $this;
  }

  public function getPrice(): ?float {
    return $this->price;
  }

  public function setPrice(float $price): static {
    $this->price = $price;

    return $this;
  }

  public function getTotal(): float {
    return $this->price * $this->quantity;
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

  public function isRedeemedWithPoints(): bool {
    return $this->redeemedWithPoints;
  }

  public function setRedeemedWithPoints(bool $redeemedWithPoints): static {
    $this->redeemedWithPoints = $redeemedWithPoints;
    return $this;
  }
}