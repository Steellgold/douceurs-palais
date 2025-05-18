<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * Entité représentant un élément de commande
 *
 * Cette classe représente un élément dans une commande, associant un produit
 * à une quantité spécifique et à un prix fixé au moment de la commande.
 * Elle permet de garder une trace des produits commandés même si le produit
 * est modifié ou supprimé ultérieurement.
 */
#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\HasLifecycleCallbacks]
class OrderItem {
  /**
   * Identifiant unique de l'élément de commande (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Commande contenant cet élément
   */
  #[ORM\ManyToOne(inversedBy: 'items')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Order $order = null;

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
  private ?int $quantity = 1;

  /**
   * Prix unitaire du produit au moment de la commande
   */
  #[ORM\Column]
  private ?float $price = 0;

  /**
   * Date de création de l'élément de commande
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour de l'élément de commande
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Indique si l'élément a été obtenu en échange de points de fidélité
   */
  #[ORM\Column(options: ['default' => false])]
  private bool $redeemedWithPoints = false;

  /**
   * Constructeur de l'élément de commande
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
   * Récupère l'identifiant de l'élément de commande
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère la commande contenant cet élément
   *
   * @return Order|null La commande
   */
  public function getOrder(): ?Order {
    return $this->order;
  }

  /**
   * Définit la commande contenant cet élément
   *
   * @param Order|null $order La nouvelle commande
   * @return static L'instance de l'élément de commande
   */
  public function setOrder(?Order $order): static {
    $this->order = $order;

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
   * Met automatiquement à jour le prix avec celui du produit.
   *
   * @param Product|null $product Le nouveau produit
   * @return static L'instance de l'élément de commande
   */
  public function setProduct(?Product $product): static {
    $this->product = $product;
    if ($product) {
      $this->price = $product->getPrice();
    }

    return $this;
  }

  /**
   * Récupère la quantité du produit
   *
   * @return int|null La quantité
   */
  public function getQuantity(): ?int {
    return $this->quantity;
  }

  /**
   * Définit la quantité du produit
   *
   * @param int $quantity La nouvelle quantité
   * @return static L'instance de l'élément de commande
   */
  public function setQuantity(int $quantity): static {
    $this->quantity = $quantity;

    return $this;
  }

  /**
   * Récupère le prix unitaire du produit
   *
   * @return float|null Le prix unitaire
   */
  public function getPrice(): ?float {
    return $this->price;
  }

  /**
   * Définit le prix unitaire du produit
   *
   * @param float $price Le nouveau prix unitaire
   * @return static L'instance de l'élément de commande
   */
  public function setPrice(float $price): static {
    $this->price = $price;

    return $this;
  }

  /**
   * Calcule le montant total de cet élément
   *
   * @return float Le montant total (prix unitaire × quantité)
   */
  public function getTotal(): float {
    return $this->price * $this->quantity;
  }

  /**
   * Récupère la date de création de l'élément de commande
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création de l'élément de commande
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance de l'élément de commande
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour de l'élément de commande
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour de l'élément de commande
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance de l'élément de commande
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
   * @return static L'instance de l'élément de commande
   */
  public function setRedeemedWithPoints(bool $redeemedWithPoints): static {
    $this->redeemedWithPoints = $redeemedWithPoints;
    return $this;
  }
}