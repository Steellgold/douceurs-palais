<?php

namespace App\Entity;

use App\Repository\IngredientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité représentant un ingrédient
 *
 * Cette classe représente un ingrédient qui peut être utilisé dans
 * différents produits. Elle stocke des informations sur l'ingrédient
 * comme ses allergènes et son statut végan.
 */
#[ORM\Entity(repositoryClass: IngredientRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Ingredient {
  /**
   * Identifiant unique de l'ingrédient (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Nom de l'ingrédient
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  private ?string $name = null;

  /**
   * Allergènes associés à cet ingrédient
   *
   * @var array<string> Tableau des allergènes
   */
  #[ORM\Column(type: 'json', nullable: true)]
  private array $allergens = [];

  /**
   * Indique si l'ingrédient est végan
   */
  #[ORM\Column]
  private bool $isVegan = false;

  /**
   * Boulangerie à laquelle appartient cet ingrédient
   */
  #[ORM\ManyToOne(inversedBy: 'ingredients')]
  #[ORM\JoinColumn(nullable: false)]
  private ?Bakery $bakery = null;

  /**
   * Date de création de l'ingrédient
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour de l'ingrédient
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Produits utilisant cet ingrédient
   *
   * @var Collection<int, Product>
   */
  #[ORM\ManyToMany(targetEntity: Product::class, mappedBy: 'ingredients')]
  private Collection $products;

  /**
   * Constructeur de l'ingrédient
   *
   * Initialise un nouvel ingrédient avec un UUID, une date de création,
   * et une collection vide pour les produits.
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->products = new ArrayCollection();
    $this->allergens = [];
  }

  /**
   * Met à jour la date de mise à jour lors de la modification de l'ingrédient
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Récupère l'identifiant de l'ingrédient
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère le nom de l'ingrédient
   *
   * @return string|null Le nom
   */
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * Définit le nom de l'ingrédient
   *
   * @param string $name Le nouveau nom
   * @return static L'instance de l'ingrédient
   */
  public function setName(string $name): static {
    $this->name = $name;
    return $this;
  }

  /**
   * Récupère les allergènes de l'ingrédient
   *
   * @return array Liste des allergènes
   */
  public function getAllergens(): array {
    return $this->allergens ?? [];
  }

  /**
   * Définit les allergènes de l'ingrédient
   *
   * @param array|null $allergens Les nouveaux allergènes
   * @return static L'instance de l'ingrédient
   */
  public function setAllergens(?array $allergens): static {
    $this->allergens = $allergens ?? [];
    return $this;
  }

  /**
   * Vérifie si l'ingrédient est végan
   *
   * @return bool true si l'ingrédient est végan, false sinon
   */
  public function isVegan(): bool {
    return $this->isVegan;
  }

  /**
   * Définit si l'ingrédient est végan
   *
   * @param bool $isVegan Le nouveau statut végan
   * @return static L'instance de l'ingrédient
   */
  public function setIsVegan(bool $isVegan): static {
    $this->isVegan = $isVegan;
    return $this;
  }

  /**
   * Récupère la boulangerie à laquelle appartient l'ingrédient
   *
   * @return Bakery|null La boulangerie
   */
  public function getBakery(): ?Bakery {
    return $this->bakery;
  }

  /**
   * Définit la boulangerie à laquelle appartient l'ingrédient
   *
   * @param Bakery|null $bakery La nouvelle boulangerie
   * @return static L'instance de l'ingrédient
   */
  public function setBakery(?Bakery $bakery): static {
    $this->bakery = $bakery;
    return $this;
  }

  /**
   * Récupère la date de création de l'ingrédient
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création de l'ingrédient
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance de l'ingrédient
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;
    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour de l'ingrédient
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour de l'ingrédient
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance de l'ingrédient
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;
    return $this;
  }

  /**
   * Récupère les produits utilisant cet ingrédient
   *
   * @return Collection<int, Product> Collection d'objets Product
   */
  public function getProducts(): Collection {
    return $this->products;
  }

  /**
   * Ajoute un produit à cet ingrédient
   *
   * @param Product $product Le produit à ajouter
   * @return static L'instance de l'ingrédient
   */
  public function addProduct(Product $product): static {
    if (!$this->products->contains($product)) {
      $this->products->add($product);
      $product->addIngredient($this);
    }
    return $this;
  }

  /**
   * Retire un produit de cet ingrédient
   *
   * @param Product $product Le produit à retirer
   * @return static L'instance de l'ingrédient
   */
  public function removeProduct(Product $product): static {
    if ($this->products->removeElement($product)) {
      $product->removeIngredient($this);
    }
    return $this;
  }
}