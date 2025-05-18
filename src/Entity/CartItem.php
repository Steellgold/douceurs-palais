<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Entité représentant un élément dans un panier
 *
 * Cette classe représente un élément dans un panier, associant un produit
 * à une quantité spécifique. Elle peut également indiquer si l'élément
 * a été obtenu en échange de points de fidélité.
 */
#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CartItem {
  /**
   * Identifiant unique de l'élément (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Panier contenant cet élément
   */
  #[ORM\ManyToOne(inversedBy: 'items')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Cart $cart = null;

  /**
   * Produit associé à cet élément
   */
  #[ORM\ManyToOne]
  #[ORM\JoinColumn(nullable: false)]
  private ?Product $product = null;

  /**
   * Quantité du produit
   */
  #[ORM\Column]
  private int $quantity = 1;

  /**
   * Date de création de l'élément
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour de l'élément
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Indique si l'élément a été obtenu en échange de points de fidélité
   */
  #[ORM\Column(options: ['default' => false])]
  private bool $redeemedWithPoints = false;


  /**
   * Constructeur de l'élément de panier
   *
   * Initialise un nouvel élément avec un UUID et une date de création.
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
  }

  /**
   * Met à jour la date de mise à jour lors de la modification de l'élément
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Récupère l'identifiant de l'élément
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère le panier contenant cet élément
   *
   * @return Cart|null Le panier
   */
  public function getCart(): ?Cart {
    return $this->cart;
  }

  /**
   * Définit le panier contenant cet élément
   *
   * @param Cart|null $cart Le nouveau panier
   * @return static L'instance de l'élément
   */
  public function setCart(?Cart $cart): static {
    $this->cart = $cart;

    return $this;
  }

  /**
   * Récupère le produit associé à cet élément
   *
   * @return Product|null Le produit
   */
  public function getProduct(): ?Product {
    return $this->product;
  }

  /**
   * Définit le produit associé à cet élément
   *
   * @param Product|null $product Le nouveau produit
   * @return static L'instance de l'élément
   */
  public function setProduct(?Product $product): static {
    $this->product = $product;

    return $this;
  }

  /**
   * Récupère la quantité du produit
   *
   * @return int La quantité
   */
  public function getQuantity(): int {
    return $this->quantity;
  }

  /**
   * Définit la quantité du produit
   *
   * @param int $quantity La nouvelle quantité
   * @return static L'instance de l'élément
   */
  public function setQuantity(int $quantity): static {
    $this->quantity = $quantity;

    return $this;
  }

  /**
   * Augmente la quantité du produit
   *
   * @param int $amount La quantité à ajouter (défaut: 1)
   * @return static L'instance de l'élément
   */
  public function increaseQuantity(int $amount = 1): static {
    $this->quantity += $amount;

    return $this;
  }

  /**
   * Diminue la quantité du produit
   *
   * La quantité ne peut pas être inférieure à 1.
   *
   * @param int $amount La quantité à retirer (défaut: 1)
   * @return static L'instance de l'élément
   */
  public function decreaseQuantity(int $amount = 1): static {
    $this->quantity = max(1, $this->quantity - $amount);

    return $this;
  }

  /**
   * Calcule le montant total de cet élément
   *
   * @return float Le montant total (prix unitaire × quantité)
   */
  public function getTotal(): float {
    return $this->product->getPrice() * $this->quantity;
  }

  /**
   * Récupère la date de création de l'élément
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création de l'élément
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance de l'élément
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour de l'élément
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour de l'élément
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance de l'élément
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Vérifie si l'élément a été obtenu en échange de points de fidélité
   *
   * @return bool true si l'élément a été obtenu avec des points, false sinon
   */
  public function isRedeemedWithPoints(): bool {
    return $this->redeemedWithPoints;
  }

  /**
   * Définit si l'élément a été obtenu en échange de points de fidélité
   *
   * @param bool $redeemedWithPoints Le nouveau statut
   * @return static L'instance de l'élément
   */
  public function setRedeemedWithPoints(bool $redeemedWithPoints): static {
    $this->redeemedWithPoints = $redeemedWithPoints;
    return $this;
  }
}