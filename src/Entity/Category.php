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

/**
 * Entité représentant une catégorie de produits
 *
 * Cette classe représente une catégorie qui peut être utilisée pour
 * regrouper et classer les produits. Chaque catégorie possède un nom,
 * une description, une position pour l'ordre d'affichage, et peut
 * être associée à une image.
 */
#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Category {
  /**
   * Identifiant unique de la catégorie (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Nom de la catégorie
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  private ?string $name = null;

  /**
   * Slug pour l'URL de la catégorie (généré à partir du nom)
   */
  #[ORM\Column(length: 255, unique: true)]
  private ?string $slug = null;

  /**
   * Description de la catégorie
   */
  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $description = null;

  /**
   * Position de la catégorie pour l'ordre d'affichage
   */
  #[ORM\Column(type: 'integer', options: ['default' => 0])]
  private int $position = 0;

  /**
   * Image associée à la catégorie
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $image = null;

  /**
   * Produits appartenant à cette catégorie
   *
   * @var Collection<int, Product>
   */
  #[ORM\OneToMany(mappedBy: 'category', targetEntity: Product::class)]
  private Collection $products;

  /**
   * Date de création de la catégorie
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour de la catégorie
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Constructeur de la catégorie
   *
   * Initialise une nouvelle catégorie avec un UUID, une date de création,
   * et une collection vide pour les produits.
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->products = new ArrayCollection();
  }

  /**
   * Calcule le slug à partir du nom de la catégorie
   *
   * @param SluggerInterface $slugger Service de génération de slugs
   */
  public function computeSlug(SluggerInterface $slugger) {
    if (!$this->slug || $this->slug === '') {
      $this->slug = strtolower($slugger->slug($this->getName())->toString());
    }
  }

  /**
   * Met à jour la date de mise à jour lors de la modification de la catégorie
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Récupère l'identifiant de la catégorie
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère le nom de la catégorie
   *
   * @return string|null Le nom
   */
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * Définit le nom de la catégorie
   *
   * @param string $name Le nouveau nom
   * @return static L'instance de la catégorie
   */
  public function setName(string $name): static {
    $this->name = $name;

    return $this;
  }

  /**
   * Récupère le slug de la catégorie
   *
   * @return string|null Le slug
   */
  public function getSlug(): ?string {
    return $this->slug;
  }

  /**
   * Définit le slug de la catégorie
   *
   * @param string $slug Le nouveau slug
   * @return static L'instance de la catégorie
   */
  public function setSlug(string $slug): static {
    $this->slug = $slug;

    return $this;
  }

  /**
   * Récupère la description de la catégorie
   *
   * @return string|null La description
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /**
   * Définit la description de la catégorie
   *
   * @param string|null $description La nouvelle description
   * @return static L'instance de la catégorie
   */
  public function setDescription(?string $description): static {
    $this->description = $description;

    return $this;
  }

  /**
   * Récupère la position de la catégorie
   *
   * @return int La position
   */
  public function getPosition(): int {
    return $this->position;
  }

  /**
   * Définit la position de la catégorie
   *
   * @param int $position La nouvelle position
   * @return static L'instance de la catégorie
   */
  public function setPosition(int $position): static {
    $this->position = $position;

    return $this;
  }

  /**
   * Récupère l'image de la catégorie
   *
   * @return string|null L'URL ou le chemin de l'image
   */
  public function getImage(): ?string {
    return $this->image;
  }

  /**
   * Définit l'image de la catégorie
   *
   * @param string|null $image La nouvelle URL ou le nouveau chemin de l'image
   * @return static L'instance de la catégorie
   */
  public function setImage(?string $image): static {
    $this->image = $image;

    return $this;
  }

  /**
   * Récupère les produits appartenant à cette catégorie
   *
   * @return Collection<int, Product> Collection d'objets Product
   */
  public function getProducts(): Collection {
    return $this->products;
  }

  /**
   * Ajoute un produit à cette catégorie
   *
   * @param Product $product Le produit à ajouter
   * @return static L'instance de la catégorie
   */
  public function addProduct(Product $product): static {
    if (!$this->products->contains($product)) {
      $this->products->add($product);
      $product->setCategory($this);
    }

    return $this;
  }

  /**
   * Retire un produit de cette catégorie
   *
   * @param Product $product Le produit à retirer
   * @return static L'instance de la catégorie
   */
  public function removeProduct(Product $product): static {
    if ($this->products->removeElement($product)) {
      if ($product->getCategory() === $this) {
        $product->setCategory(null);
      }
    }

    return $this;
  }

  /**
   * Récupère la date de création de la catégorie
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création de la catégorie
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance de la catégorie
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour de la catégorie
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour de la catégorie
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance de la catégorie
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }
}