<?php

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Cart {
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $sessionId = null;

  #[ORM\ManyToOne(inversedBy: 'carts')]
  private ?User $user = null;

  #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
  private Collection $items;

  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->items = new ArrayCollection();
    $this->createdAt = new \DateTimeImmutable();
  }

  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  public function getId(): ?string {
    return $this->id;
  }

  public function getSessionId(): ?string {
    return $this->sessionId;
  }

  public function setSessionId(?string $sessionId): static {
    $this->sessionId = $sessionId;

    return $this;
  }

  public function getUser(): ?User {
    return $this->user;
  }

  public function setUser(?User $user): static {
    $this->user = $user;

    return $this;
  }

  /**
   * @return Collection<int, CartItem>
   */
  public function getItems(): Collection {
    return $this->items;
  }

  public function addItem(CartItem $item): static {
    if (!$this->items->contains($item)) {
      $this->items->add($item);
      $item->setCart($this);
    }

    return $this;
  }

  public function removeItem(CartItem $item): static {
    if ($this->items->removeElement($item)) {
      // set the owning side to null (unless already changed)
      if ($item->getCart() === $this) {
        $item->setCart(null);
      }
    }

    return $this;
  }

  public function getItemByProduct(Product $product): ?CartItem {
    foreach ($this->items as $item) {
      if ($item->getProduct()->getId() === $product->getId()) {
        return $item;
      }
    }

    return null;
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

  public function getTotalItems(): int {
    $total = 0;
    foreach ($this->items as $item) {
      $total += $item->getQuantity();
    }
    return $total;
  }

  public function getTotalPrice(): float {
    $total = 0;
    foreach ($this->items as $item) {
      $total += $item->getProduct()->getPrice() * $item->getQuantity();
    }
    return $total;
  }

  public function isEmpty(): bool {
    return $this->items->isEmpty();
  }

  public function clear(): void {
    foreach ($this->items as $item) {
      $this->removeItem($item);
    }
  }

  public function getUniqueShops(): array {
    $shops = [];
    foreach ($this->items as $item) {
      $bakeryId = $item->getProduct()->getBakery()->getId();
      if (!isset($shops[$bakeryId])) {
        $shops[$bakeryId] = [
          'bakery' => $item->getProduct()->getBakery(),
          'items' => [],
          'total' => 0
        ];
      }
      $shops[$bakeryId]['items'][] = $item;
      $shops[$bakeryId]['total'] += $item->getProduct()->getPrice() * $item->getQuantity();
    }
    return $shops;
  }

  public function hasMultipleShops(): bool {
    return count($this->getUniqueShops()) > 1;
  }
}