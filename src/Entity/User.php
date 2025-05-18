<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité utilisateur
 *
 * Cette classe représente un utilisateur du système avec toutes ses propriétés
 * et relations. Elle implémente les interfaces nécessaires pour
 * l'authentification Symfony.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
  /**
   * Identifiant unique de l'utilisateur (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Adresse email de l'utilisateur (unique)
   */
  #[ORM\Column(length: 180, unique: true)]
  #[Assert\NotBlank(message: 'Veuillez entrer un email')]
  #[Assert\Email(message: 'Cet email n\'est pas valide')]
  private ?string $email = null;

  /**
   * Rôles attribués à l'utilisateur
   */
  #[ORM\Column]
  private array $roles = [];

  /**
   * Mot de passe haché de l'utilisateur
   *
   * @var string Le mot de passe haché
   */
  #[ORM\Column]
  private string $password = "";

  /**
   * Prénom de l'utilisateur
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'Veuillez entrer votre prénom')]
  private ?string $firstName = null;

  /**
   * Nom de famille de l'utilisateur
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'Veuillez entrer votre nom')]
  private ?string $lastName = null;

  /**
   * Numéro de téléphone de l'utilisateur
   */
  #[ORM\Column(length: 15, nullable: true)]
  #[Assert\Regex(pattern: '/^\+?[0-9]{10,15}$/', message: 'Ce numéro de téléphone n\'est pas valide')]
  private ?string $phone = null;

  /**
   * Date de création du compte utilisateur
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour du compte utilisateur
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Collection des adresses de l'utilisateur
   *
   * @var Collection<int, Address>
   */
  #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
  private Collection $addresses;

  /**
   * Indique si l'email de l'utilisateur a été vérifié
   */
  #[ORM\Column(nullable: true)]
  private ?bool $isVerified = false;

  /**
   * Jeton pour la réinitialisation du mot de passe
   */
  #[ORM\Column(length: 100, nullable: true)]
  private ?string $resetToken = null;

  /**
   * Date d'expiration du jeton de réinitialisation
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $resetTokenExpiresAt = null;

  /**
   * Collection des boulangeries favorites de l'utilisateur
   *
   * @var Collection<int, Bakery>
   */
  #[ORM\ManyToMany(targetEntity: Bakery::class, inversedBy: 'favoriteByUsers')]
  #[ORM\JoinTable(name: 'user_favorite_bakeries')]
  private Collection $favoriteBakeries;

  /**
   * Collection des paniers de l'utilisateur
   *
   * @var Collection<int, Cart>
   */
  #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'user', orphanRemoval: true)]
  private Collection $carts;

  /**
   * Boulangerie gérée par l'utilisateur (si l'utilisateur est un boulanger)
   */
  #[ORM\ManyToOne(inversedBy: 'bakers')]
  #[ORM\JoinColumn(nullable: true)]
  private ?Bakery $managedBakery = null;

  /**
   * Collection des commandes de l'utilisateur
   *
   * @var Collection<int, Order>
   */
  #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
  private Collection $orders;

  /**
   * Nombre de points de fidélité de l'utilisateur
   */
  #[ORM\Column(options: ['default' => 0])]
  private int $loyaltyPoints = 0;

  /**
   * Constructeur de l'utilisateur
   *
   * Initialise un nouvel utilisateur avec un UUID, une date de création,
   * et des collections vides pour les adresses, boulangeries favorites,
   * paniers et commandes.
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->addresses = new ArrayCollection();
    $this->favoriteBakeries = new ArrayCollection();
    $this->carts = new ArrayCollection();
    $this->orders = new ArrayCollection();
  }

  /**
   * Met à jour la date de mise à jour lors de la modification de l'utilisateur
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Récupère l'identifiant de l'utilisateur
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère l'email de l'utilisateur
   *
   * @return string|null L'adresse email
   */
  public function getEmail(): ?string {
    return $this->email;
  }

  /**
   * Définit l'email de l'utilisateur
   *
   * @param string $email La nouvelle adresse email
   * @return static L'instance de l'utilisateur
   */
  public function setEmail(string $email): static {
    $this->email = $email;

    return $this;
  }

  /**
   * Récupère l'identifiant visuel représentant l'utilisateur
   *
   * Implémentation de l'interface UserInterface
   *
   * @return string L'email de l'utilisateur
   */
  public function getUserIdentifier(): string {
    return (string)$this->email;
  }

  /**
   * Récupère les rôles de l'utilisateur
   *
   * Garantit que chaque utilisateur a au moins le rôle ROLE_USER
   *
   * @return array<string> Les rôles de l'utilisateur
   */
  public function getRoles(): array {
    $roles = $this->roles;
    // Garantit que chaque utilisateur a au moins ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  /**
   * Définit les rôles de l'utilisateur
   *
   * @param array $roles Les nouveaux rôles
   * @return static L'instance de l'utilisateur
   */
  public function setRoles(array $roles): static {
    $this->roles = $roles;

    return $this;
  }

  /**
   * Récupère le mot de passe haché de l'utilisateur
   *
   * Implémentation de l'interface PasswordAuthenticatedUserInterface
   *
   * @return string Le mot de passe haché
   */
  public function getPassword(): string {
    return $this->password;
  }

  /**
   * Définit le mot de passe haché de l'utilisateur
   *
   * @param string $password Le nouveau mot de passe haché
   * @return static L'instance de l'utilisateur
   */
  public function setPassword(string $password): static {
    $this->password = $password;

    return $this;
  }

  /**
   * Efface les données sensibles temporaires de l'utilisateur
   *
   * Implémentation de l'interface UserInterface
   */
  public function eraseCredentials(): void {
    // Si vous stockez des données temporaires sensibles sur l'utilisateur, les effacer ici
    // $this->plainPassword = null;
  }

  /**
   * Récupère le prénom de l'utilisateur
   *
   * @return string|null Le prénom
   */
  public function getFirstName(): ?string {
    return $this->firstName;
  }

  /**
   * Définit le prénom de l'utilisateur
   *
   * @param string $firstName Le nouveau prénom
   * @return static L'instance de l'utilisateur
   */
  public function setFirstName(string $firstName): static {
    $this->firstName = $firstName;

    return $this;
  }

  /**
   * Récupère le nom de famille de l'utilisateur
   *
   * @return string|null Le nom de famille
   */
  public function getLastName(): ?string {
    return $this->lastName;
  }

  /**
   * Définit le nom de famille de l'utilisateur
   *
   * @param string $lastName Le nouveau nom de famille
   * @return static L'instance de l'utilisateur
   */
  public function setLastName(string $lastName): static {
    $this->lastName = $lastName;

    return $this;
  }

  /**
   * Récupère le nom complet de l'utilisateur
   *
   * @return string Le prénom et le nom concaténés
   */
  public function getFullName(): string {
    return $this->firstName . ' ' . $this->lastName;
  }

  /**
   * Récupère le numéro de téléphone de l'utilisateur
   *
   * @return string|null Le numéro de téléphone
   */
  public function getPhone(): ?string {
    return $this->phone;
  }

  /**
   * Définit le numéro de téléphone de l'utilisateur
   *
   * @param string|null $phone Le nouveau numéro de téléphone
   * @return static L'instance de l'utilisateur
   */
  public function setPhone(?string $phone): static {
    $this->phone = $phone;

    return $this;
  }

  /**
   * Récupère la date de création du compte
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création du compte
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance de l'utilisateur
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour du compte
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour du compte
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance de l'utilisateur
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Récupère toutes les adresses de l'utilisateur
   *
   * @return Collection<int, Address> Collection d'objets Address
   */
  public function getAddresses(): Collection {
    return $this->addresses;
  }

  /**
   * Ajoute une adresse à l'utilisateur
   *
   * @param Address $address L'adresse à ajouter
   * @return static L'instance de l'utilisateur
   */
  public function addAddress(Address $address): static {
    if (!$this->addresses->contains($address)) {
      $this->addresses->add($address);
      $address->setUser($this);
    }

    return $this;
  }

  /**
   * Supprime une adresse de l'utilisateur
   *
   * @param Address $address L'adresse à supprimer
   * @return static L'instance de l'utilisateur
   */
  public function removeAddress(Address $address): static {
    if ($this->addresses->removeElement($address)) {
      if ($address->getUser() === $this) {
        $address->setUser(null);
      }
    }

    return $this;
  }

  /**
   * Récupère l'adresse principale de l'utilisateur
   *
   * Si aucune adresse n'est marquée comme principale, définit la première adresse
   * comme principale si elle existe.
   *
   * @return Address|null L'adresse principale ou null si aucune adresse
   */
  public function getPrimaryAddress(): ?Address {
    foreach ($this->addresses as $address) {
      if ($address->isIsPrimary()) {
        return $address;
      }
    }

    if (!$this->addresses->isEmpty()) {
      $firstAddress = $this->addresses->first();
      $firstAddress->setIsPrimary(true);
      return $firstAddress;
    }

    return null;
  }

  /**
   * Vérifie si l'utilisateur a au moins une adresse
   *
   * @return bool true si l'utilisateur a au moins une adresse, false sinon
   */
  public function hasAddresses(): bool {
    return !$this->addresses->isEmpty();
  }

  /**
   * Définit une adresse comme adresse principale
   *
   * @param Address $primaryAddress L'adresse à définir comme principale
   * @return self L'instance de l'utilisateur
   * @throws \InvalidArgumentException Si l'adresse n'appartient pas à l'utilisateur
   */
  public function setAddressAsPrimary(Address $primaryAddress): self {
    if (!$this->addresses->contains($primaryAddress)) {
      throw new \InvalidArgumentException('Cette adresse n\'appartient pas à cet utilisateur');
    }

    foreach ($this->addresses as $address) {
      $address->setIsPrimary(false);
    }

    $primaryAddress->setIsPrimary(true);
    return $this;
  }

  /**
   * Vérifie si l'email de l'utilisateur a été vérifié
   *
   * @return bool|null true si vérifié, false sinon
   */
  public function isIsVerified(): ?bool {
    return $this->isVerified;
  }

  /**
   * Définit le statut de vérification de l'email
   *
   * @param bool|null $isVerified Le nouveau statut de vérification
   * @return static L'instance de l'utilisateur
   */
  public function setIsVerified(?bool $isVerified): static {
    $this->isVerified = $isVerified;

    return $this;
  }

  /**
   * Récupère le jeton de réinitialisation du mot de passe
   *
   * @return string|null Le jeton de réinitialisation
   */
  public function getResetToken(): ?string {
    return $this->resetToken;
  }

  /**
   * Définit le jeton de réinitialisation du mot de passe
   *
   * @param string|null $resetToken Le nouveau jeton de réinitialisation
   * @return static L'instance de l'utilisateur
   */
  public function setResetToken(?string $resetToken): static {
    $this->resetToken = $resetToken;

    return $this;
  }

  /**
   * Récupère la date d'expiration du jeton de réinitialisation
   *
   * @return \DateTimeImmutable|null La date d'expiration
   */
  public function getResetTokenExpiresAt(): ?\DateTimeImmutable {
    return $this->resetTokenExpiresAt;
  }

  /**
   * Définit la date d'expiration du jeton de réinitialisation
   *
   * @param \DateTimeImmutable|null $resetTokenExpiresAt La nouvelle date d'expiration
   * @return static L'instance de l'utilisateur
   */
  public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static {
    $this->resetTokenExpiresAt = $resetTokenExpiresAt;

    return $this;
  }

  /**
   * Récupère les boulangeries favorites de l'utilisateur
   *
   * @return Collection<int, Bakery> Collection d'objets Bakery
   */
  public function getFavoriteBakeries(): Collection {
    return $this->favoriteBakeries;
  }

  /**
   * Ajoute une boulangerie aux favoris de l'utilisateur
   *
   * @param Bakery $bakery La boulangerie à ajouter aux favoris
   * @return static L'instance de l'utilisateur
   */
  public function addFavoriteBakery(Bakery $bakery): static {
    if (!$this->favoriteBakeries->contains($bakery)) {
      $this->favoriteBakeries->add($bakery);
    }

    return $this;
  }

  /**
   * Retire une boulangerie des favoris de l'utilisateur
   *
   * @param Bakery $bakery La boulangerie à retirer des favoris
   * @return static L'instance de l'utilisateur
   */
  public function removeFavoriteBakery(Bakery $bakery): static {
    $this->favoriteBakeries->removeElement($bakery);

    return $this;
  }

  /**
   * Vérifie si une boulangerie est dans les favoris de l'utilisateur
   *
   * @param Bakery $bakery La boulangerie à vérifier
   * @return bool true si la boulangerie est un favori, false sinon
   */
  public function hasFavoriteBakery(Bakery $bakery): bool {
    return $this->favoriteBakeries->contains($bakery);
  }

  /**
   * Récupère les paniers de l'utilisateur
   *
   * @return Collection<int, Cart> Collection d'objets Cart
   */
  public function getCarts(): Collection {
    return $this->carts;
  }

  /**
   * Ajoute un panier à l'utilisateur
   *
   * @param Cart $cart Le panier à ajouter
   * @return static L'instance de l'utilisateur
   */
  public function addCart(Cart $cart): static {
    if (!$this->carts->contains($cart)) {
      $this->carts->add($cart);
      $cart->setUser($this);
    }

    return $this;
  }

  /**
   * Retire un panier de l'utilisateur
   *
   * @param Cart $cart Le panier à retirer
   * @return static L'instance de l'utilisateur
   */
  public function removeCart(Cart $cart): static {
    if ($this->carts->removeElement($cart)) {
      if ($cart->getUser() === $this) {
        $cart->setUser(null);
      }
    }

    return $this;
  }

  /**
   * Récupère la boulangerie gérée par l'utilisateur
   *
   * @return Bakery|null La boulangerie gérée ou null
   */
  public function getManagedBakery(): ?Bakery {
    return $this->managedBakery;
  }

  /**
   * Définit la boulangerie gérée par l'utilisateur
   *
   * @param Bakery|null $bakery La nouvelle boulangerie gérée
   * @return static L'instance de l'utilisateur
   */
  public function setManagedBakery(?Bakery $bakery): static {
    $this->managedBakery = $bakery;

    return $this;
  }

  /**
   * Vérifie si l'utilisateur est un boulanger
   *
   * @return bool true si l'utilisateur a le rôle ROLE_BAKER, false sinon
   */
  public function isBaker(): bool {
    return in_array('ROLE_BAKER', $this->getRoles());
  }

  /**
   * Récupère les commandes de l'utilisateur
   *
   * @return Collection<int, Order> Collection d'objets Order
   */
  public function getOrders(): Collection {
    return $this->orders;
  }

  /**
   * Ajoute une commande à l'utilisateur
   *
   * @param Order $order La commande à ajouter
   * @return static L'instance de l'utilisateur
   */
  public function addOrder(Order $order): static {
    if (!$this->orders->contains($order)) {
      $this->orders->add($order);
      $order->setUser($this);
    }

    return $this;
  }

  /**
   * Retire une commande de l'utilisateur
   *
   * @param Order $order La commande à retirer
   * @return static L'instance de l'utilisateur
   */
  public function removeOrder(Order $order): static {
    if ($this->orders->removeElement($order)) {
      if ($order->getUser() === $this) {
        $order->setUser(null);
      }
    }

    return $this;
  }

  /**
   * Récupère le nombre de points de fidélité de l'utilisateur
   *
   * @return int Le nombre de points de fidélité
   */
  public function getLoyaltyPoints(): int {
    return $this->loyaltyPoints;
  }

  /**
   * Définit le nombre de points de fidélité de l'utilisateur
   *
   * @param int $loyaltyPoints Le nouveau nombre de points
   * @return static L'instance de l'utilisateur
   */
  public function setLoyaltyPoints(int $loyaltyPoints): static {
    $this->loyaltyPoints = $loyaltyPoints;
    return $this;
  }

  /**
   * Ajoute des points de fidélité à l'utilisateur
   *
   * @param int $points Le nombre de points à ajouter
   * @return static L'instance de l'utilisateur
   */
  public function addLoyaltyPoints(int $points): static {
    $this->loyaltyPoints += $points;
    return $this;
  }

  /**
   * Dépense des points de fidélité de l'utilisateur
   *
   * @param int $points Le nombre de points à dépenser
   * @return static L'instance de l'utilisateur
   * @throws \InvalidArgumentException Si l'utilisateur n'a pas assez de points
   */
  public function spendLoyaltyPoints(int $points): static {
    if ($this->loyaltyPoints < $points) {
      throw new \InvalidArgumentException('Pas assez de points de fidélité');
    }
    $this->loyaltyPoints -= $points;
    return $this;
  }
}