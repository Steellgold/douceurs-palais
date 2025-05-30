<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité représentant un produit
 *
 * Cette classe représente un produit vendu par une boulangerie.
 * Elle contient toutes les informations liées au produit : nom, description,
 * prix, ingrédients, valeurs nutritionnelles, allergènes, etc.
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Product {
  /**
   * Identifiant unique du produit (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Nom du produit
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  private ?string $name = null;

  /**
   * Slug pour l'URL du produit (généré à partir du nom)
   */
  #[ORM\Column(length: 255, unique: true)]
  private ?string $slug = null;

  /**
   * Description du produit
   */
  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $description = null;

  /**
   * Prix du produit
   */
  #[ORM\Column]
  #[Assert\NotBlank]
  #[Assert\Positive]
  private ?float $price = null;

  /**
   * Images du produit
   *
   * @var array<string> Tableau d'URLs vers les images
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $images = [];

  /**
   * Ingrédients du produit (ancien format, conservé pour compatibilité)
   *
   * @var array<string> Liste des ingrédients en texte
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $ingredients = [];

  /**
   * Allergènes présents dans le produit (ancien format, conservé pour compatibilité)
   *
   * @var array<string> Liste des allergènes
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $allergenes = [];

  /**
   * Nutriscore du produit (A, B, C, D, E)
   */
  #[ORM\Column(length: 2, nullable: true)]
  private ?string $nutriscore = null;

  /**
   * Valeurs nutritionnelles du produit
   *
   * @var array<string, float> Tableau associatif des valeurs nutritionnelles
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $nutritionalValues = [];

  /**
   * Conseils de conservation du produit
   */
  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $conservation = null;

  /**
   * Suggestions d'accompagnement pour le produit
   *
   * @var array<string> Liste des suggestions d'accompagnement
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $pairings = [];

  /**
   * Date de création du produit
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour du produit
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Boulangerie proposant ce produit
   */
  #[ORM\ManyToOne(inversedBy: 'products')]
  private ?Bakery $bakery = null;

  /**
   * Indice de popularité du produit
   */
  #[ORM\Column(nullable: true)]
  private ?int $popularity = 0;

  /**
   * Nombre de points de fidélité requis pour obtenir ce produit gratuitement
   *
   * Si null, le produit n'est pas disponible avec des points de fidélité.
   */
  #[ORM\Column(nullable: true)]
  private ?int $requiredPoints = null;

  /**
   * Indique si le produit a été obtenu en échange de points de fidélité
   */
  #[ORM\Column(options: ['default' => false])]
  private bool $redeemedWithPoints = false;

  /**
   * Catégorie du produit
   */
  #[ORM\ManyToOne(inversedBy: 'products')]
  private ?Category $category = null;

  /**
   * Ingrédients utilisés dans ce produit (nouvelle relation)
   *
   * @var Collection<int, Ingredient>
   */
  #[ORM\ManyToMany(targetEntity: Ingredient::class, inversedBy: 'products')]
  private Collection $productIngredients;

  /**
   * Indique si le produit est végan
   */
  #[ORM\Column(options: ['default' => false])]
  private bool $isVegan = false;

  /**
   * Taux de TVA du produit (en pourcentage)
   */
  #[ORM\Column]
  private ?float $taxRate = 5.5;

  /**
   * Constructeur du produit
   *
   * Initialise un nouveau produit avec un UUID et une date de création.
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();

    $this->images = [];
    $this->ingredients = [];
    $this->allergenes = [];
    $this->nutritionalValues = [];
    $this->pairings = [];
    $this->productIngredients = new ArrayCollection();
  }

  /**
   * Calcule le slug à partir du nom du produit
   *
   * @param SluggerInterface $slugger Service de génération de slugs
   */
  public function computeSlug(SluggerInterface $slugger): void {
    if (!$this->slug) {
      $this->slug = strtolower($slugger->slug($this->getName())->toString());
    }
  }

  /**
   * Met à jour la date de mise à jour lors de la modification du produit
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Récupère l'identifiant du produit
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Définit l'identifiant du produit
   *
   * @param string $id Le nouvel identifiant
   * @return static L'instance du produit
   */
  public function setId(string $id): static {
    $this->id = $id;

    return $this;
  }

  /**
   * Récupère le nom du produit
   *
   * @return string|null Le nom
   */
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * Définit le nom du produit
   *
   * @param string $name Le nouveau nom
   * @return static L'instance du produit
   */
  public function setName(string $name): static {
    $this->name = $name;

    return $this;
  }

  /**
   * Récupère le slug du produit
   *
   * @return string|null Le slug
   */
  public function getSlug(): ?string {
    return $this->slug;
  }

  /**
   * Définit le slug du produit
   *
   * @param string $slug Le nouveau slug
   * @return static L'instance du produit
   */
  public function setSlug(string $slug): static {
    $this->slug = $slug;

    return $this;
  }

  /**
   * Récupère la description du produit
   *
   * @return string|null La description
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /**
   * Définit la description du produit
   *
   * @param string|null $description La nouvelle description
   * @return static L'instance du produit
   */
  public function setDescription(?string $description): static {
    $this->description = $description;

    return $this;
  }

  /**
   * Récupère le prix du produit
   *
   * @return float|null Le prix
   */
  public function getPrice(): ?float {
    return $this->price;
  }

  /**
   * Définit le prix du produit
   *
   * @param float $price Le nouveau prix
   * @return static L'instance du produit
   */
  public function setPrice(float $price): static {
    $this->price = $price;

    return $this;
  }

  /**
   * Récupère les images du produit
   *
   * @return array Les URLs des images
   */
  public function getImages(): array {
    return $this->images;
  }

  /**
   * Définit les images du produit
   *
   * @param array|null $images Les nouvelles URLs d'images
   * @return static L'instance du produit
   */
  public function setImages(?array $images): static {
    $this->images = $images ?? [];
    return $this;
  }

  /**
   * Récupère l'image principale du produit
   *
   * @return string|null L'URL de l'image principale
   */
  public function getMainImage(): ?string {
    return $this->images[0] ?? null;
  }

  /**
   * Ajoute une image au produit
   *
   * @param string $image L'URL de l'image à ajouter
   * @return static L'instance du produit
   */
  public function addImage(string $image): static {
    if (!in_array($image, $this->images, true)) {
      $this->images[] = $image;
    }

    return $this;
  }

  /**
   * Supprime une image du produit
   *
   * @param string $image L'URL de l'image à supprimer
   * @return static L'instance du produit
   */
  public function removeImage(string $image): static {
    $key = array_search($image, $this->images, true);
    if ($key !== false) {
      unset($this->images[$key]);
      $this->images = array_values($this->images);
    }

    return $this;
  }

  /**
   * Récupère les ingrédients du produit (ancien format)
   *
   * @return array Liste des ingrédients
   */
  public function getIngredients(): array {
    return $this->ingredients;
  }

  /**
   * Définit les ingrédients du produit (ancien format)
   *
   * @param array|null $ingredients Les nouveaux ingrédients
   * @return static L'instance du produit
   */
  public function setIngredients(?array $ingredients): static {
    $this->ingredients = $ingredients ?? [];

    return $this;
  }

  /**
   * Récupère les allergènes du produit (ancien format)
   *
   * @return array Liste des allergènes
   */
  public function getAllergenes(): array {
    return $this->allergenes;
  }

  /**
   * Définit les allergènes du produit (ancien format)
   *
   * @param array|null $allergenes Les nouveaux allergènes
   * @return static L'instance du produit
   */
  public function setAllergenes(?array $allergenes): static {
    $this->allergenes = $allergenes ?? [];

    return $this;
  }

  /**
   * Récupère le nutriscore du produit
   *
   * @return string|null Le nutriscore
   */
  public function getNutriscore(): ?string {
    return $this->nutriscore;
  }

  /**
   * Définit le nutriscore du produit
   *
   * @param string|null $nutriscore Le nouveau nutriscore
   * @return static L'instance du produit
   */
  public function setNutriscore(?string $nutriscore): static {
    $this->nutriscore = $nutriscore;

    return $this;
  }

  /**
   * Récupère les valeurs nutritionnelles du produit
   *
   * @return array Les valeurs nutritionnelles
   */
  public function getNutritionalValues(): array {
    return $this->nutritionalValues;
  }

  /**
   * Définit les valeurs nutritionnelles du produit
   *
   * @param array|null $nutritionalValues Les nouvelles valeurs nutritionnelles
   * @return static L'instance du produit
   */
  public function setNutritionalValues(?array $nutritionalValues): static {
    $this->nutritionalValues = $nutritionalValues ?? [];

    return $this;
  }

  /**
   * Récupère les conseils de conservation du produit
   *
   * @return string|null Les conseils de conservation
   */
  public function getConservation(): ?string {
    return $this->conservation;
  }

  /**
   * Définit les conseils de conservation du produit
   *
   * @param string|null $conservation Les nouveaux conseils de conservation
   * @return static L'instance du produit
   */
  public function setConservation(?string $conservation): static {
    $this->conservation = $conservation;

    return $this;
  }

  /**
   * Récupère les suggestions d'accompagnement pour le produit
   *
   * @return array Les suggestions d'accompagnement
   */
  public function getPairings(): array {
    return $this->pairings;
  }

  /**
   * Définit les suggestions d'accompagnement pour le produit
   *
   * @param array|null $pairings Les nouvelles suggestions d'accompagnement
   * @return static L'instance du produit
   */
  public function setPairings(?array $pairings): static {
    $this->pairings = $pairings ?? [];

    return $this;
  }

  /**
   * Récupère la date de création du produit
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création du produit
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance du produit
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour du produit
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour du produit
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance du produit
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Récupère la boulangerie proposant ce produit
   *
   * @return Bakery|null La boulangerie
   */
  public function getBakery(): ?Bakery {
    return $this->bakery;
  }

  /**
   * Définit la boulangerie proposant ce produit
   *
   * @param Bakery|null $bakery La nouvelle boulangerie
   * @return static L'instance du produit
   */
  public function setBakery(?Bakery $bakery): static {
    $this->bakery = $bakery;

    return $this;
  }

  /**
   * Récupère l'indice de popularité du produit
   *
   * @return int|null L'indice de popularité
   */
  public function getPopularity(): ?int {
    return $this->popularity;
  }

  /**
   * Définit l'indice de popularité du produit
   *
   * @param int|null $popularity Le nouvel indice de popularité
   * @return static L'instance du produit
   */
  public function setPopularity(?int $popularity): static {
    $this->popularity = $popularity;

    return $this;
  }

  /**
   * Récupère la catégorie du produit
   *
   * @return Category|null La catégorie
   */
  public function getCategory(): ?Category {
    return $this->category;
  }

  /**
   * Définit la catégorie du produit
   *
   * @param Category|null $category La nouvelle catégorie
   * @return static L'instance du produit
   */
  public function setCategory(?Category $category): static {
    $this->category = $category;

    return $this;
  }

  /**
   * Récupère le nombre de points de fidélité requis pour obtenir ce produit
   *
   * @return int|null Le nombre de points requis
   */
  public function getRequiredPoints(): ?int {
    return $this->requiredPoints;
  }

  /**
   * Définit le nombre de points de fidélité requis pour obtenir ce produit
   *
   * @param int|null $requiredPoints Le nouveau nombre de points requis
   * @return static L'instance du produit
   */
  public function setRequiredPoints(?int $requiredPoints): static {
    $this->requiredPoints = $requiredPoints;
    return $this;
  }

  /**
   * Vérifie si le produit est disponible avec des points de fidélité
   *
   * @return bool true si le produit est disponible avec des points, false sinon
   */
  public function isAvailableWithPoints(): bool {
    return $this->requiredPoints !== null;
  }

  /**
   * Vérifie si le produit a été obtenu en échange de points de fidélité
   *
   * @return bool true si le produit a été obtenu avec des points, false sinon
   */
  public function isRedeemedWithPoints(): bool {
    return $this->redeemedWithPoints;
  }

  /**
   * Définit si le produit a été obtenu en échange de points de fidélité
   *
   * @param bool $redeemedWithPoints Le nouveau statut
   * @return static L'instance du produit
   */
  public function setRedeemedWithPoints(bool $redeemedWithPoints): static {
    $this->redeemedWithPoints = $redeemedWithPoints;
    return $this;
  }

  /**
   * Récupère les ingrédients du produit (nouvelle relation)
   *
   * @return Collection<int, Ingredient> Collection d'objets Ingredient
   */
  public function getProductIngredients(): Collection {
    return $this->productIngredients;
  }

  /**
   * Ajoute un ingrédient au produit
   *
   * @param Ingredient $ingredient L'ingrédient à ajouter
   * @return static L'instance du produit
   */
  public function addIngredient(Ingredient $ingredient): static {
    if (!$this->productIngredients->contains($ingredient)) {
      $this->productIngredients->add($ingredient);
    }
    return $this;
  }

  /**
   * Retire un ingrédient du produit
   *
   * @param Ingredient $ingredient L'ingrédient à retirer
   * @return static L'instance du produit
   */
  public function removeIngredient(Ingredient $ingredient): static {
    $this->productIngredients->removeElement($ingredient);
    return $this;
  }

  /**
   * Récupère tous les allergènes des ingrédients du produit
   *
   * @return array Tableau associatif des allergènes avec leurs ingrédients source
   */
  public function getAllIngredientsAllergens(): array {
    $allergens = [];
    foreach ($this->productIngredients as $ingredient) {
      foreach ($ingredient->getAllergens() as $allergen) {
        if (!isset($allergens[$allergen])) {
          $allergens[$allergen] = [];
        }
        $allergens[$allergen][] = $ingredient->getName();
      }
    }

    // Dédupliquer les ingrédients pour chaque allergène
    foreach ($allergens as $allergen => $ingredients) {
      $allergens[$allergen] = array_unique($ingredients);
    }

    return $allergens;
  }

  /**
   * Vérifie si le produit est végan
   *
   * @return bool true si le produit est végan, false sinon
   */
  public function isVegan(): bool {
    return $this->isVegan;
  }

  /**
   * Définit si le produit est végan
   *
   * @param bool $isVegan Le nouveau statut végan
   * @return static L'instance du produit
   */
  public function setIsVegan(bool $isVegan): static {
    $this->isVegan = $isVegan;
    return $this;
  }

  /**
   * Vérifie si tous les ingrédients du produit sont végans
   *
   * @return bool true si tous les ingrédients sont végans, false sinon
   */
  public function hasAllVeganIngredients(): bool {
    if ($this->productIngredients->isEmpty()) {
      return true;
    }

    foreach ($this->productIngredients as $ingredient) {
      if (!$ingredient->isVegan()) {
        return false;
      }
    }

    return true;
  }

  // Getter et setter pour Product.php :
  public function getTaxRate(): ?float {
    return $this->taxRate;
  }

  public function setTaxRate(float $taxRate): static {
    $this->taxRate = $taxRate;
    return $this;
  }

  /**
   * Calcule le prix HT du produit
   */
  public function getPriceExcludingTax(): float {
    return $this->price / (1 + ($this->taxRate / 100));
  }

  /**
   * Calcule le montant de TVA pour ce produit
   */
  public function getTaxAmount(): float {
    return $this->price - $this->getPriceExcludingTax();
  }

}