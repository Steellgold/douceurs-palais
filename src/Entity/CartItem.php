<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CartItem {
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  #[ORM\ManyToOne(inversedBy: 'items')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Cart $cart = null;

  #[ORM\ManyToOne]
  #[ORM\JoinColumn(nullable: false)]
  private ?Product $product = null;

  #[ORM\Column]
  private int $quantity = 1;

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

  public function getCart(): ?Cart {
    return $this->cart;
  }

  public function setCart(?Cart $cart): static {
    $this->cart = $cart;

    return $this;
  }

  public function getProduct(): ?Product {
    return $this->product;
  }

  public function setProduct(?Product $product): static {
    $this->product = $product;

    return $this;
  }

  public function getQuantity(): int {
    return $this->quantity;
  }

  public function setQuantity(int $quantity): static {
    $this->quantity = $quantity;

    return $this;
  }

  public function increaseQuantity(int $amount = 1): static {
    $this->quantity += $amount;

    return $this;
  }

  public function decreaseQuantity(int $amount = 1): static {
    $this->quantity = max(1, $this->quantity - $amount);

    return $this;
  }

  public function getTotal(): float {
    return $this->product->getPrice() * $this->quantity;
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