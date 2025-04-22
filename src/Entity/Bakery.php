<?php

namespace App\Entity;

use App\Repository\BakeryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BakeryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Bakery {
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  private ?string $name = null;

  #[ORM\Column(length: 255, unique: true)]
  private ?string $slug = null;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $description = null;

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $story = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  private ?string $address = null;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $city = null;

  #[ORM\Column(length: 10, nullable: true)]
  private ?string $postalCode = null;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $phone = null;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $email = null;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $website = null;

  #[ORM\Column(nullable: true)]
  private ?float $rating = null;

  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $images = [];

  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $openingHours = [];

  #[ORM\OneToMany(mappedBy: 'bakery', targetEntity: Product::class)]
  private Collection $products;

  #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoriteBakeries')]
  private Collection $favoriteByUsers;

  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  #[ORM\OneToOne(inversedBy: 'managedBakery', targetEntity: User::class)]
  #[ORM\JoinColumn(nullable: true)]
  private ?User $baker = null;

  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->products = new ArrayCollection();
    $this->favoriteByUsers = new ArrayCollection();
  }

  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  public function getId(): ?string {
    return $this->id;
  }

  public function getName(): ?string {
    return $this->name;
  }

  public function setName(string $name): static {
    $this->name = $name;

    return $this;
  }

  public function getSlug(): ?string {
    return $this->slug;
  }

  public function setSlug(string $slug): static {
    $this->slug = $slug;

    return $this;
  }

  public function getDescription(): ?string {
    return $this->description;
  }

  public function setDescription(?string $description): static {
    $this->description = $description;

    return $this;
  }

  public function getStory(): ?string {
    return $this->story;
  }

  public function setStory(?string $story): static {
    $this->story = $story;

    return $this;
  }

  public function getAddress(): ?string {
    return $this->address;
  }

  public function setAddress(string $address): static {
    $this->address = $address;

    return $this;
  }

  public function getCity(): ?string {
    return $this->city;
  }

  public function setCity(?string $city): static {
    $this->city = $city;

    return $this;
  }

  public function getPostalCode(): ?string {
    return $this->postalCode;
  }

  public function setPostalCode(?string $postalCode): static {
    $this->postalCode = $postalCode;

    return $this;
  }

  public function getPhone(): ?string {
    return $this->phone;
  }

  public function setPhone(?string $phone): static {
    $this->phone = $phone;

    return $this;
  }

  public function getEmail(): ?string {
    return $this->email;
  }

  public function setEmail(?string $email): static {
    $this->email = $email;

    return $this;
  }

  public function getWebsite(): ?string {
    return $this->website;
  }

  public function setWebsite(?string $website): static {
    $this->website = $website;

    return $this;
  }

  public function getRating(): ?float {
    return $this->rating;
  }

  public function setRating(?float $rating): static {
    $this->rating = $rating;

    return $this;
  }

  public function getImages(): array {
    return $this->images;
  }

  public function setImages(?array $images): static {
    $this->images = $images ?? [];

    return $this;
  }

  public function getMainImage(): ?string {
    return $this->images[0] ?? null;
  }

  public function getOpeningHours(): array {
    return $this->openingHours;
  }

  public function setOpeningHours(?array $openingHours): static {
    $this->openingHours = $openingHours ?? [];

    return $this;
  }

  /**
   * @return Collection<int, Product>
   */
  public function getProducts(): Collection {
    return $this->products;
  }

  public function addProduct(Product $product): static {
    if (!$this->products->contains($product)) {
      $this->products->add($product);
      $product->setBakery($this);
    }

    return $this;
  }

  public function removeProduct(Product $product): static {
    if ($this->products->removeElement($product)) {
      if ($product->getBakery() === $this) {
        $product->setBakery(null);
      }
    }

    return $this;
  }

  /**
   * @return Collection<int, User>
   */
  public function getFavoriteByUsers(): Collection {
    return $this->favoriteByUsers;
  }

  public function addFavoriteByUser(User $user): static {
    if (!$this->favoriteByUsers->contains($user)) {
      $this->favoriteByUsers->add($user);
      $user->addFavoriteBakery($this);
    }

    return $this;
  }

  public function removeFavoriteByUser(User $user): static {
    if ($this->favoriteByUsers->removeElement($user)) {
      $user->removeFavoriteBakery($this);
    }

    return $this;
  }

  public function isFavoriteByUser(User $user): bool {
    return $this->favoriteByUsers->contains($user);
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

  public function getBaker(): ?User {
    return $this->baker;
  }

  public function setBaker(?User $user): static {
    $this->baker = $user;

    return $this;
  }
}