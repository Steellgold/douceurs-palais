<?php

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Entité représentant un panier d'achat
 *
 * Cette classe représente un panier d'achat qui peut être associé
 * à un utilisateur connecté ou à une session pour un utilisateur anonyme.
 * Un panier contient des éléments (CartItem) qui font référence à des produits.
 */
#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Cart {
  /**
   * Identifiant unique du panier (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Identifiant de session pour les utilisateurs non connectés
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $sessionId = null;

  /**
   * Utilisateur propriétaire du panier (null pour un utilisateur non connecté)
   */
  #[ORM\ManyToOne(inversedBy: 'carts')]
  private ?User $user = null;

  /**
   * Éléments du panier
   *
   * @var Collection<int, CartItem>
   */
  #[ORM\OneToMany(mappedBy: 'cart', targetEntity: CartItem::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
  private Collection $items;

  /**
   * Date de création du panier
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour du panier
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Constructeur du panier
   *
   * Initialise un nouveau panier avec un UUID, une date de création,
   * et une collection vide pour les éléments.
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->items = new ArrayCollection();
    $this->createdAt = new \DateTimeImmutable();
  }

  /**
   * Met à jour la date de mise à jour lors de la modification du panier
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Récupère l'identifiant du panier
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère l'identifiant de session
   *
   * @return string|null L'identifiant de session
   */
  public function getSessionId(): ?string {
    return $this->sessionId;
  }

  /**
   * Définit l'identifiant de session
   *
   * @param string|null $sessionId Le nouvel identifiant de session
   * @return static L'instance du panier
   */
  public function setSessionId(?string $sessionId): static {
    $this->sessionId = $sessionId;

    return $this;
  }

  /**
   * Récupère l'utilisateur propriétaire du panier
   *
   * @return User|null L'utilisateur
   */
  public function getUser(): ?User {
    return $this->user;
  }

  /**
   * Définit l'utilisateur propriétaire du panier
   *
   * @param User|null $user Le nouvel utilisateur
   * @return static L'instance du panier
   */
  public function setUser(?User $user): static {
    $this->user = $user;

    return $this;
  }

  /**
   * Récupère les éléments du panier
   *
   * @return Collection<int, CartItem> Collection d'objets CartItem
   */
  public function getItems(): Collection {
    return $this->items;
  }

  /**
   * Ajoute un élément au panier
   *
   * @param CartItem $item L'élément à ajouter
   * @return static L'instance du panier
   */
  public function addItem(CartItem $item): static {
    if (!$this->items->contains($item)) {
      $this->items->add($item);
      $item->setCart($this);
    }

    return $this;
  }

  /**
   * Retire un élément du panier
   *
   * @param CartItem $item L'élément à retirer
   * @return static L'instance du panier
   */
  public function removeItem(CartItem $item): static {
    if ($this->items->removeElement($item)) {
      // Ne définit pas la relation côté CartItem à null que si le panier actuel est le propriétaire
      if ($item->getCart() === $this) {
        $item->setCart(null);
      }
    }

    return $this;
  }

  /**
   * Recherche un élément du panier par produit
   *
   * @param Product $product Le produit à rechercher
   * @return CartItem|null L'élément trouvé ou null
   */
  public function getItemByProduct(Product $product): ?CartItem {
    foreach ($this->items as $item) {
      if ($item->getProduct()->getId() === $product->getId()) {
        return $item;
      }
    }

    return null;
  }

  /**
   * Récupère la date de création du panier
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création du panier
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance du panier
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour du panier
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour du panier
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance du panier
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Calcule le nombre total d'articles dans le panier
   *
   * @return int Le nombre total d'articles
   */
  public function getTotalItems(): int {
    $total = 0;
    foreach ($this->items as $item) {
      $total += $item->getQuantity();
    }
    return $total;
  }

  /**
   * Vérifie si le panier est vide
   *
   * @return bool true si le panier est vide, false sinon
   */
  public function isEmpty(): bool {
    return $this->items->isEmpty();
  }

  /**
   * Vide le panier en retirant tous les éléments
   */
  public function clear(): void {
    foreach ($this->items as $item) {
      $this->removeItem($item);
    }
  }

  /**
   * Récupère les boulangeries uniques présentes dans le panier
   *
   * Utilisé pour gérer le cas où des produits de plusieurs boulangeries
   * sont présents dans le panier (ce qui n'est normalement pas autorisé).
   *
   * @return array Tableau associatif des boulangeries avec leurs produits et totaux
   */
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

  /**
   * Vérifie si le panier contient des produits de plusieurs boulangeries
   *
   * @return bool true si le panier contient des produits de plusieurs boulangeries, false sinon
   */
  public function hasMultipleShops(): bool {
    return count($this->getUniqueShops()) > 1;
  }

  /**
   * Calcule le montant total du panier
   *
   * Ne prend pas en compte les produits obtenus avec des points de fidélité.
   *
   * @return float Le montant total
   */
  public function getTotalPrice(): float {
    $total = 0;
    foreach ($this->items as $item) {
      if (!$item->isRedeemedWithPoints()) {
        $total += $item->getProduct()->getPrice() * $item->getQuantity();
      }
    }
    return $total;
  }
}