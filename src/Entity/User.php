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

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
class User implements UserInterface, PasswordAuthenticatedUserInterface {
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  #[ORM\Column(length: 180, unique: true)]
  #[Assert\NotBlank(message: 'Veuillez entrer un email')]
  #[Assert\Email(message: 'Cet email n\'est pas valide')]
  private ?string $email = null;

  #[ORM\Column]
  private array $roles = [];

  /**
   * @var string The hashed password
   */
  #[ORM\Column]
  private string $password = "";

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'Veuillez entrer votre prénom')]
  private ?string $firstName = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'Veuillez entrer votre nom')]
  private ?string $lastName = null;

  #[ORM\Column(length: 15, nullable: true)]
  #[Assert\Regex(pattern: '/^\+?[0-9]{10,15}$/', message: 'Ce numéro de téléphone n\'est pas valide')]
  private ?string $phone = null;

  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  #[ORM\OneToMany(targetEntity: Address::class, mappedBy: 'user', cascade: ['persist'], orphanRemoval: true)]
  private Collection $addresses;

  #[ORM\Column(nullable: true)]
  private ?bool $isVerified = false;

  #[ORM\Column(length: 100, nullable: true)]
  private ?string $resetToken = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $resetTokenExpiresAt = null;

  #[ORM\ManyToMany(targetEntity: Bakery::class, inversedBy: 'favoriteByUsers')]
  #[ORM\JoinTable(name: 'user_favorite_bakeries')]
  private Collection $favoriteBakeries;

  #[ORM\OneToMany(targetEntity: Cart::class, mappedBy: 'user', orphanRemoval: true)]
  private Collection $carts;

  #[ORM\ManyToOne(inversedBy: 'bakers')]
  #[ORM\JoinColumn(nullable: true)]
  private ?Bakery $managedBakery = null;

  #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
  private Collection $orders;

  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->addresses = new ArrayCollection();
    $this->favoriteBakeries = new ArrayCollection();
    $this->carts = new ArrayCollection();
    $this->orders = new ArrayCollection();
  }

  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  public function getId(): ?string {
    return $this->id;
  }

  public function getEmail(): ?string {
    return $this->email;
  }

  public function setEmail(string $email): static {
    $this->email = $email;

    return $this;
  }

  /**
   * A visual identifier that represents this user.
   *
   * @see UserInterface
   */
  public function getUserIdentifier(): string {
    return (string)$this->email;
  }

  /**
   * @see UserInterface
   */
  public function getRoles(): array {
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  public function setRoles(array $roles): static {
    $this->roles = $roles;

    return $this;
  }

  /**
   * @see PasswordAuthenticatedUserInterface
   */
  public function getPassword(): string {
    return $this->password;
  }

  public function setPassword(string $password): static {
    $this->password = $password;

    return $this;
  }

  /**
   * @see UserInterface
   */
  public function eraseCredentials(): void {
    // If you store any temporary, sensitive data on the user, clear it here
    // $this->plainPassword = null;
  }

  public function getFirstName(): ?string {
    return $this->firstName;
  }

  public function setFirstName(string $firstName): static {
    $this->firstName = $firstName;

    return $this;
  }

  public function getLastName(): ?string {
    return $this->lastName;
  }

  public function setLastName(string $lastName): static {
    $this->lastName = $lastName;

    return $this;
  }

  public function getFullName(): string {
    return $this->firstName . ' ' . $this->lastName;
  }

  public function getPhone(): ?string {
    return $this->phone;
  }

  public function setPhone(?string $phone): static {
    $this->phone = $phone;

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

  /**
   * @return Collection<int, Address>
   */
  public function getAddresses(): Collection {
    return $this->addresses;
  }

  public function addAddress(Address $address): static {
    if (!$this->addresses->contains($address)) {
      $this->addresses->add($address);
      $address->setUser($this);
    }

    return $this;
  }

  public function removeAddress(Address $address): static {
    if ($this->addresses->removeElement($address)) {
      if ($address->getUser() === $this) {
        $address->setUser(null);
      }
    }

    return $this;
  }

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

  public function hasAddresses(): bool {
    return !$this->addresses->isEmpty();
  }

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

  public function isIsVerified(): ?bool {
    return $this->isVerified;
  }

  public function setIsVerified(?bool $isVerified): static {
    $this->isVerified = $isVerified;

    return $this;
  }

  public function getResetToken(): ?string {
    return $this->resetToken;
  }

  public function setResetToken(?string $resetToken): static {
    $this->resetToken = $resetToken;

    return $this;
  }

  public function getResetTokenExpiresAt(): ?\DateTimeImmutable {
    return $this->resetTokenExpiresAt;
  }

  public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): static {
    $this->resetTokenExpiresAt = $resetTokenExpiresAt;

    return $this;
  }

  /**
   * @return Collection<int, Bakery>
   */
  public function getFavoriteBakeries(): Collection {
    return $this->favoriteBakeries;
  }

  public function addFavoriteBakery(Bakery $bakery): static {
    if (!$this->favoriteBakeries->contains($bakery)) {
      $this->favoriteBakeries->add($bakery);
    }

    return $this;
  }

  public function removeFavoriteBakery(Bakery $bakery): static {
    $this->favoriteBakeries->removeElement($bakery);

    return $this;
  }

  public function hasFavoriteBakery(Bakery $bakery): bool {
    return $this->favoriteBakeries->contains($bakery);
  }

  /**
   * @return Collection<int, Cart>
   */
  public function getCarts(): Collection {
    return $this->carts;
  }

  public function addCart(Cart $cart): static {
    if (!$this->carts->contains($cart)) {
      $this->carts->add($cart);
      $cart->setUser($this);
    }

    return $this;
  }

  public function removeCart(Cart $cart): static {
    if ($this->carts->removeElement($cart)) {
      if ($cart->getUser() === $this) {
        $cart->setUser(null);
      }
    }

    return $this;
  }

  /**
   * @return Bakery|null
   */
  public function getManagedBakery(): ?Bakery {
    return $this->managedBakery;
  }

  /**
   * @param Bakery|null $bakery
   * @return $this
   */
  public function setManagedBakery(?Bakery $bakery): static {
    $this->managedBakery = $bakery;

    return $this;
  }

  /**
   * @return bool
   */
  public function isBaker(): bool {
    return in_array('ROLE_BAKER', $this->getRoles());
  }

  /**
   * @return Collection<int, Order>
   */
  public function getOrders(): Collection {
    return $this->orders;
  }

  public function addOrder(Order $order): static {
    if (!$this->orders->contains($order)) {
      $this->orders->add($order);
      $order->setUser($this);
    }

    return $this;
  }

  public function removeOrder(Order $order): static {
    if ($this->orders->removeElement($order)) {
      if ($order->getUser() === $this) {
        $order->setUser(null);
      }
    }

    return $this;
  }
}