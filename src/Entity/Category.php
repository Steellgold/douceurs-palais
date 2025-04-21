<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category {
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

  #[ORM\Column(type: 'integer', options: ['default' => 0])]
  private int $position = 0;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $image = null;

  #[ORM\OneToMany(mappedBy: 'category', targetEntity: Product::class)]
  private Collection $products;

  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->products = new ArrayCollection();
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

  public function getPosition(): int {
    return $this->position;
  }

  public function setPosition(int $position): static {
    $this->position = $position;

    return $this;
  }

  public function getImage(): ?string {
    return $this->image;
  }

  public function setImage(?string $image): static {
    $this->image = $image;

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
      $product->setCategory($this);
    }

    return $this;
  }

  public function removeProduct(Product $product): static {
    if ($this->products->removeElement($product)) {
      if ($product->getCategory() === $this) {
        $product->setCategory(null);
      }
    }

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