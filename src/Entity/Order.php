<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Random\RandomException;
use Symfony\Component\Uid\Uuid;

/**
 * Entité représentant une commande
 *
 * Cette classe représente une commande passée par un utilisateur.
 * Elle contient toutes les informations nécessaires pour traiter la commande :
 * éléments commandés, adresses, montant total, statut, etc.
 */
#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[ORM\HasLifecycleCallbacks]
class Order {
  /**
   * Constantes pour les différents statuts de commande
   */
  const STATUS_PENDING = 'pending';                  // En attente
  const STATUS_PAYMENT_PROCESSING = 'payment_processing'; // Paiement en cours
  const STATUS_PAID = 'paid';                        // Payée
  const STATUS_PREPARING = 'preparing';              // En préparation
  const STATUS_SHIPPED = 'shipped';                  // Expédiée
  const STATUS_DELIVERED = 'delivered';              // Livrée
  const STATUS_CANCELLED = 'cancelled';              // Annulée
  const STATUS_REFUNDED = 'refunded';                // Remboursée

  /**
   * Identifiant unique de la commande (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Référence lisible de la commande (ex: ORD-ABCD1234)
   */
  #[ORM\Column(length: 255)]
  private ?string $reference = null;

  /**
   * Utilisateur ayant passé la commande
   */
  #[ORM\ManyToOne(inversedBy: 'orders')]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $user = null;

  /**
   * Montant total de la commande
   */
  #[ORM\Column]
  private ?float $totalAmount = 0;

  /**
   * Statut actuel de la commande
   */
  #[ORM\Column(length: 50)]
  private ?string $status = self::STATUS_PENDING;

  /**
   * Identifiant de la session de paiement Stripe
   */
  #[ORM\Column(nullable: true)]
  private ?string $stripeSessionId = null;

  /**
   * Identifiant de l'intention de paiement Stripe
   */
  #[ORM\Column(nullable: true)]
  private ?string $stripePaymentIntentId = null;

  /**
   * Date de création de la commande
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour de la commande
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Adresse de livraison (format JSON)
   *
   * @var array<string, string> Tableau associatif des détails de l'adresse
   */
  #[ORM\Column(type: 'json', nullable: true)]
  private array $shippingAddress = [];

  /**
   * Adresse de facturation (format JSON)
   *
   * @var array<string, string> Tableau associatif des détails de l'adresse
   */
  #[ORM\Column(type: 'json', nullable: true)]
  private array $billingAddress = [];

  /**
   * Éléments de la commande
   *
   * @var Collection<int, OrderItem>
   */
  #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
  private Collection $items;

  /**
   * Jeton unique pour accéder à la commande sans être connecté
   */
  #[ORM\Column(length: 64, nullable: true)]
  private ?string $token = null;

  /**
   * Taux de TVA appliqué à la commande (en pourcentage)
   */
  #[ORM\Column]
  private ?float $taxRate = 5.5;

  /**
   * Montant de la TVA
   */
  #[ORM\Column]
  private ?float $taxAmount = 0;

  /**
   * Montant HT de la commande
   */
  #[ORM\Column]
  private ?float $subtotalAmount = 0;

  /**
   * Constructeur de la commande
   *
   * Initialise une nouvelle commande avec un UUID, une date de création,
   * une référence générée automatiquement, un jeton unique pour l'accès anonyme,
   * et une collection vide pour les éléments.
   * @throws RandomException
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->reference = $this->generateReference();
    $this->items = new ArrayCollection();
    $this->token = bin2hex(random_bytes(32));
  }

  /**
   * Met à jour la date de mise à jour lors de la modification de la commande
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Génère une référence unique pour la commande
   *
   * @return string La référence générée
   */
  private function generateReference(): string {
    return 'ORD-' . strtoupper(substr(uniqid('', true), 0, 8));
  }

  /**
   * Récupère l'identifiant de la commande
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère la référence de la commande
   *
   * @return string|null La référence
   */
  public function getReference(): ?string {
    return $this->reference;
  }

  /**
   * Définit la référence de la commande
   *
   * @param string $reference La nouvelle référence
   * @return static L'instance de la commande
   */
  public function setReference(string $reference): static {
    $this->reference = $reference;

    return $this;
  }

  /**
   * Récupère l'utilisateur ayant passé la commande
   *
   * @return User|null L'utilisateur
   */
  public function getUser(): ?User {
    return $this->user;
  }

  /**
   * Définit l'utilisateur ayant passé la commande
   *
   * @param User|null $user Le nouvel utilisateur
   * @return static L'instance de la commande
   */
  public function setUser(?User $user): static {
    $this->user = $user;

    return $this;
  }

  /**
   * Récupère le montant total de la commande
   *
   * @return float|null Le montant total
   */
  public function getTotalAmount(): ?float {
    return $this->totalAmount;
  }

  /**
   * Définit le montant total de la commande
   *
   * @param float $totalAmount Le nouveau montant total
   * @return static L'instance de la commande
   */
  public function setTotalAmount(float $totalAmount): static {
    $this->totalAmount = $totalAmount;

    return $this;
  }

  /**
   * Récupère le statut de la commande
   *
   * @return string|null Le statut
   */
  public function getStatus(): ?string {
    return $this->status;
  }

  /**
   * Définit le statut de la commande
   *
   * @param string $status Le nouveau statut
   * @return static L'instance de la commande
   */
  public function setStatus(string $status): static {
    $this->status = $status;

    return $this;
  }

  /**
   * Récupère l'identifiant de la session de paiement Stripe
   *
   * @return string|null L'identifiant de session
   */
  public function getStripeSessionId(): ?string {
    return $this->stripeSessionId;
  }

  /**
   * Définit l'identifiant de la session de paiement Stripe
   *
   * @param string|null $stripeSessionId Le nouvel identifiant de session
   * @return static L'instance de la commande
   */
  public function setStripeSessionId(?string $stripeSessionId): static {
    $this->stripeSessionId = $stripeSessionId;

    return $this;
  }

  /**
   * Récupère l'identifiant de l'intention de paiement Stripe
   *
   * @return string|null L'identifiant d'intention de paiement
   */
  public function getStripePaymentIntentId(): ?string {
    return $this->stripePaymentIntentId;
  }

  /**
   * Définit l'identifiant de l'intention de paiement Stripe
   *
   * @param string|null $stripePaymentIntentId Le nouvel identifiant d'intention de paiement
   * @return static L'instance de la commande
   */
  public function setStripePaymentIntentId(?string $stripePaymentIntentId): static {
    $this->stripePaymentIntentId = $stripePaymentIntentId;

    return $this;
  }

  /**
   * Récupère la date de création de la commande
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création de la commande
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance de la commande
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour de la commande
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour de la commande
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance de la commande
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Récupère l'adresse de livraison
   *
   * @return array Les détails de l'adresse de livraison
   */
  public function getShippingAddress(): array {
    return $this->shippingAddress;
  }

  /**
   * Définit l'adresse de livraison
   *
   * @param array $shippingAddress La nouvelle adresse de livraison
   * @return static L'instance de la commande
   */
  public function setShippingAddress(array $shippingAddress): static {
    $this->shippingAddress = $shippingAddress;

    return $this;
  }

  /**
   * Récupère l'adresse de facturation
   *
   * @return array Les détails de l'adresse de facturation
   */
  public function getBillingAddress(): array {
    return $this->billingAddress;
  }

  /**
   * Définit l'adresse de facturation
   *
   * @param array $billingAddress La nouvelle adresse de facturation
   * @return static L'instance de la commande
   */
  public function setBillingAddress(array $billingAddress): static {
    $this->billingAddress = $billingAddress;

    return $this;
  }

  /**
   * Récupère les éléments de la commande
   *
   * @return Collection<int, OrderItem> Collection d'objets OrderItem
   */
  public function getItems(): Collection {
    return $this->items;
  }

  /**
   * Ajoute un élément à la commande
   *
   * @param OrderItem $item L'élément à ajouter
   * @return static L'instance de la commande
   */
  public function addItem(OrderItem $item): static {
    if (!$this->items->contains($item)) {
      $this->items->add($item);
      $item->setOrder($this);
    }

    return $this;
  }

  /**
   * Retire un élément de la commande
   *
   * @param OrderItem $item L'élément à retirer
   * @return static L'instance de la commande
   */
  public function removeItem(OrderItem $item): static {
    if ($this->items->removeElement($item)) {
      if ($item->getOrder() === $this) {
        $item->setOrder(null);
      }
    }

    return $this;
  }

  /**
   * Récupère le jeton d'accès à la commande
   *
   * @return string|null Le jeton
   */
  public function getToken(): ?string {
    return $this->token;
  }

  /**
   * Définit le jeton d'accès à la commande
   *
   * @param string|null $token Le nouveau jeton
   * @return static L'instance de la commande
   */
  public function setToken(?string $token): static {
    $this->token = $token;

    return $this;
  }

  /**
   * Regroupe les éléments de la commande par boulangerie
   *
   * @return array Tableau associatif des boulangeries avec leurs produits et totaux
   */
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

  /**
   * Récupère le libellé du statut de la commande
   *
   * @return string Le libellé du statut en français
   */
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

  /**
   * Récupère la classe CSS correspondant au statut de la commande
   *
   * @return string La classe CSS pour le style du statut
   */
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

  /**
   * @param float $taxRate
   * @return $this
   */
  public function setTaxRate(float $taxRate): static {
    $this->taxRate = $taxRate;
    return $this;
  }

  /**
   * @return float|null
   */
  public function getTaxAmount(): ?float {
    return $this->taxAmount;
  }

  /**
   * @param float $taxAmount
   * @return $this
   */
  public function setTaxAmount(float $taxAmount): static {
    $this->taxAmount = $taxAmount;
    return $this;
  }

  /**
   * @return float|null
   */
  public function getSubtotalAmount(): ?float {
    return $this->subtotalAmount;
  }

  /**
   * @param float $subtotalAmount
   * @return $this
   */
  public function setSubtotalAmount(float $subtotalAmount): static {
    $this->subtotalAmount = $subtotalAmount;
    return $this;
  }

  /**
   * @return void
   */
  public function calculateTaxAmounts(): void {
    $this->taxAmount = $this->totalAmount * ($this->taxRate / 100);
    $this->subtotalAmount = $this->totalAmount - $this->taxAmount;
  }

  /**
   * @return float|null
   */
  public function getTaxRate(): ?float {
    return $this->taxRate;
  }
}