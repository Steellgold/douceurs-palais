<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product {
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

  #[ORM\Column]
  #[Assert\NotBlank]
  #[Assert\Positive]
  private ?float $price = null;

  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $images = [];

  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $ingredients = [];

  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $allergenes = [];

  #[ORM\Column(length: 2, nullable: true)]
  private ?string $nutriscore = null;

  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $nutritionalValues = [];

  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $conservation = null;

  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $pairings = [];

  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
  }

  public function computeSlug(SluggerInterface $slugger) {
    if (!$this->slug || $this->slug === '') {
      $this->slug = strtolower($slugger->slug($this->getName())->toString());
    }
  }

  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  public function getId(): ?string {
    return $this->id;
  }

  public function setId(string $id): static {
    $this->id = $id;

    return $this;
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

  public function getPrice(): ?float {
    return $this->price;
  }

  public function setPrice(float $price): static {
    $this->price = $price;

    return $this;
  }

  public function getImages(): array {
    return $this->images;
  }

  public function setImages(?array $images): static {
    $this->images = $images ?? [];

    return $this;
  }

  public function addImage(string $image): static {
    if (!in_array($image, $this->images, true)) {
      $this->images[] = $image;
    }

    return $this;
  }

  public function removeImage(string $image): static {
    $key = array_search($image, $this->images, true);
    if ($key !== false) {
      unset($this->images[$key]);
      $this->images = array_values($this->images);
    }

    return $this;
  }

  public function getIngredients(): array {
    return $this->ingredients;
  }

  public function setIngredients(?array $ingredients): static {
    $this->ingredients = $ingredients ?? [];

    return $this;
  }

  public function getAllergenes(): array {
    return $this->allergenes;
  }

  public function setAllergenes(?array $allergenes): static {
    $this->allergenes = $allergenes ?? [];

    return $this;
  }

  public function getNutriscore(): ?string {
    return $this->nutriscore;
  }

  public function setNutriscore(?string $nutriscore): static {
    $this->nutriscore = $nutriscore;

    return $this;
  }

  public function getNutritionalValues(): array {
    return $this->nutritionalValues;
  }

  public function setNutritionalValues(?array $nutritionalValues): static {
    $this->nutritionalValues = $nutritionalValues ?? [];

    return $this;
  }

  public function getConservation(): ?string {
    return $this->conservation;
  }

  public function setConservation(?string $conservation): static {
    $this->conservation = $conservation;

    return $this;
  }

  public function getPairings(): array {
    return $this->pairings;
  }

  public function setPairings(?array $pairings): static {
    $this->pairings = $pairings ?? [];

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
}