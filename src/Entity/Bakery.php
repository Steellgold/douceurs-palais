<?php

namespace App\Entity;

use App\Repository\BakeryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité représentant une boulangerie
 *
 * Cette classe représente une boulangerie avec toutes ses informations :
 * coordonnées, description, horaires d'ouverture, produits associés,
 * boulangers qui la gèrent, etc.
 */
#[ORM\Entity(repositoryClass: BakeryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Bakery {
  /**
   * Identifiant unique de la boulangerie (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Nom de la boulangerie
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  private ?string $name = null;

  /**
   * Slug pour l'URL de la boulangerie (généré à partir du nom)
   */
  #[ORM\Column(length: 255, unique: true)]
  private ?string $slug = null;

  /**
   * Titre court de la boulangerie
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $title = null;

  /**
   * Histoire détaillée de la boulangerie
   */
  #[ORM\Column(type: Types::TEXT, nullable: true)]
  private ?string $description = null;

  /**
   * Adresse de la boulangerie
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  private ?string $address = null;

  /**
   * Ville de la boulangerie
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $city = null;

  /**
   * Code postal de la boulangerie
   */
  #[ORM\Column(length: 10, nullable: true)]
  private ?string $postalCode = null;

  /**
   * Numéro de téléphone de la boulangerie
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $phone = null;

  /**
   * Email de contact de la boulangerie
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $email = null;

  /**
   * Site web de la boulangerie
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $website = null;

  /**
   * Note moyenne de la boulangerie
   */
  #[ORM\Column(nullable: true)]
  private ?float $rating = null;

  /**
   * Images de la boulangerie
   *
   * @var array<string> Tableau d'URLs vers les images
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $images = [];

  /**
   * Logo de la boulangerie
   *
   * @var string|null URL de l'image du logo
   */
  #[ORM\Column(length: 300, nullable: false)]
  private ?string $logo = '';

  /**
   * Horaires d'ouverture de la boulangerie
   *
   * @var array<string, string> Tableau associatif jour → horaires
   */
  #[ORM\Column(type: Types::JSON, nullable: true)]
  private array $openingHours = [];

  /**
   * Produits proposés par la boulangerie
   *
   * @var Collection<int, Product>
   */
  #[ORM\OneToMany(targetEntity: Product::class, mappedBy: 'bakery')]
  private Collection $products;

  /**
   * Utilisateurs ayant mis la boulangerie en favori
   *
   * @var Collection<int, User>
   */
  #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'favoriteBakeries')]
  private Collection $favoriteByUsers;

  /**
   * Date de création de la boulangerie
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour de la boulangerie
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Boulangers qui gèrent la boulangerie
   *
   * @var Collection<int, User>
   */
  #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'managedBakery')]
  private Collection $bakers;

  /**
   * Ingrédients de la boulangerie
   *
   * @var Collection<int, Ingredient>
   */
  #[ORM\OneToMany(targetEntity: Ingredient::class, mappedBy: 'bakery', orphanRemoval: true)]
  private Collection $ingredients;

  /**
   * Constructeur de la boulangerie
   *
   * Initialise une nouvelle boulangerie avec un UUID, une date de création,
   * et des collections vides pour les produits, utilisateurs favoris et boulangers.
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
    $this->products = new ArrayCollection();
    $this->favoriteByUsers = new ArrayCollection();
    $this->bakers = new ArrayCollection();
    $this->ingredients = new ArrayCollection();
  }

  /**
   * Met à jour la date de mise à jour lors de la modification de la boulangerie
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Récupère l'identifiant de la boulangerie
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère le nom de la boulangerie
   *
   * @return string|null Le nom
   */
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * Définit le nom de la boulangerie
   *
   * @param string $name Le nouveau nom
   * @return static L'instance de la boulangerie
   */
  public function setName(string $name): static {
    $this->name = $name;

    return $this;
  }

  /**
   * Récupère le slug de la boulangerie
   *
   * @return string|null Le slug
   */
  public function getSlug(): ?string {
    return $this->slug;
  }

  /**
   * Définit le slug de la boulangerie
   *
   * @param string $slug Le nouveau slug
   * @return static L'instance de la boulangerie
   */
  public function setSlug(string $slug): static {
    $this->slug = $slug;

    return $this;
  }

  /**
   * @return string|null
   */
  public function getTitle(): ?string {
    return $this->title;
  }

  /**
   * @param string|null $title
   */
  public function setTitle(?string $title): void {
    $this->title = $title;
  }

  /**
   * Récupère la description de la boulangerie
   *
   * @return string|null La description
   */
  public function getDescription(): ?string {
    return $this->description;
  }

  /**
   * Définit la description de la boulangerie
   *
   * @param string|null $description La nouvelle description
   * @return static L'instance de la boulangerie
   */
  public function setDescription(?string $description): static {
    $this->description = $description;

    return $this;
  }

  /**
   * Récupère l'adresse de la boulangerie
   *
   * @return string|null L'adresse
   */
  public function getAddress(): ?string {
    return $this->address;
  }

  /**
   * Définit l'adresse de la boulangerie
   *
   * @param string $address La nouvelle adresse
   * @return static L'instance de la boulangerie
   */
  public function setAddress(string $address): static {
    $this->address = $address;

    return $this;
  }

  /**
   * Récupère la ville de la boulangerie
   *
   * @return string|null La ville
   */
  public function getCity(): ?string {
    return $this->city;
  }

  /**
   * Définit la ville de la boulangerie
   *
   * @param string|null $city La nouvelle ville
   * @return static L'instance de la boulangerie
   */
  public function setCity(?string $city): static {
    $this->city = $city;

    return $this;
  }

  /**
   * Récupère le code postal de la boulangerie
   *
   * @return string|null Le code postal
   */
  public function getPostalCode(): ?string {
    return $this->postalCode;
  }

  /**
   * Définit le code postal de la boulangerie
   *
   * @param string|null $postalCode Le nouveau code postal
   * @return static L'instance de la boulangerie
   */
  public function setPostalCode(?string $postalCode): static {
    $this->postalCode = $postalCode;

    return $this;
  }

  /**
   * Récupère le numéro de téléphone de la boulangerie
   *
   * @return string|null Le numéro de téléphone
   */
  public function getPhone(): ?string {
    return $this->phone;
  }

  /**
   * Définit le numéro de téléphone de la boulangerie
   *
   * @param string|null $phone Le nouveau numéro de téléphone
   * @return static L'instance de la boulangerie
   */
  public function setPhone(?string $phone): static {
    $this->phone = $phone;

    return $this;
  }

  /**
   * Récupère l'email de la boulangerie
   *
   * @return string|null L'email
   */
  public function getEmail(): ?string {
    return $this->email;
  }

  /**
   * Définit l'email de la boulangerie
   *
   * @param string|null $email Le nouvel email
   * @return static L'instance de la boulangerie
   */
  public function setEmail(?string $email): static {
    $this->email = $email;

    return $this;
  }

  /**
   * Récupère le site web de la boulangerie
   *
   * @return string|null Le site web
   */
  public function getWebsite(): ?string {
    return $this->website;
  }

  /**
   * Définit le site web de la boulangerie
   *
   * @param string|null $website Le nouveau site web
   * @return static L'instance de la boulangerie
   */
  public function setWebsite(?string $website): static {
    $this->website = $website;

    return $this;
  }

  /**
   * Récupère la note de la boulangerie
   *
   * @return float|null La note
   */
  public function getRating(): ?float {
    return $this->rating;
  }

  /**
   * Définit la note de la boulangerie
   *
   * @param float|null $rating La nouvelle note
   * @return static L'instance de la boulangerie
   */
  public function setRating(?float $rating): static {
    $this->rating = $rating;

    return $this;
  }

  /**
   * Récupère les images de la boulangerie
   *
   * @return array Les URLs des images
   */
  public function getImages(): array {
    return $this->images;
  }

  /**
   * Définit les images de la boulangerie
   *
   * @param array|null $images Les nouvelles URLs d'images
   * @return static L'instance de la boulangerie
   */
  public function setImages(?array $images): static {
    $this->images = $images ?? [];

    return $this;
  }

  public function getLogo(): ?string {
    return $this->logo;
  }

  public function setLogo(?string $logo): static {
    $this->logo = $logo;
    return $this;
  }

  /**
   * Récupère l'image principale de la boulangerie
   *
   * @return string|null L'URL de l'image principale
   */
  public function getMainImage(): ?string {
    return $this->images[0] ?? null;
  }

  /**
   * Récupère les horaires d'ouverture de la boulangerie
   *
   * @return array Les horaires d'ouverture
   */
  public function getOpeningHours(): array {
    return $this->openingHours;
  }

  /**
   * Définit les horaires d'ouverture de la boulangerie
   *
   * @param array|null $openingHours Les nouveaux horaires d'ouverture
   * @return static L'instance de la boulangerie
   */
  public function setOpeningHours(?array $openingHours): static {
    $this->openingHours = $openingHours ?? [];

    return $this;
  }

  /**
   * Récupère les produits de la boulangerie
   *
   * @return Collection<int, Product> Collection d'objets Product
   */
  public function getProducts(): Collection {
    return $this->products;
  }

  /**
   * Ajoute un produit à la boulangerie
   *
   * @param Product $product Le produit à ajouter
   * @return static L'instance de la boulangerie
   */
  public function addProduct(Product $product): static {
    if (!$this->products->contains($product)) {
      $this->products->add($product);
      $product->setBakery($this);
    }

    return $this;
  }

  /**
   * Retire un produit de la boulangerie
   *
   * @param Product $product Le produit à retirer
   * @return static L'instance de la boulangerie
   */
  public function removeProduct(Product $product): static {
    if ($this->products->removeElement($product)) {
      if ($product->getBakery() === $this) {
        $product->setBakery(null);
      }
    }

    return $this;
  }

  /**
   * Récupère les utilisateurs ayant mis la boulangerie en favori
   *
   * @return Collection<int, User> Collection d'objets User
   */
  public function getFavoriteByUsers(): Collection {
    return $this->favoriteByUsers;
  }

  /**
   * Ajoute un utilisateur à la liste des favoris
   *
   * @param User $user L'utilisateur à ajouter
   * @return static L'instance de la boulangerie
   */
  public function addFavoriteByUser(User $user): static {
    if (!$this->favoriteByUsers->contains($user)) {
      $this->favoriteByUsers->add($user);
      $user->addFavoriteBakery($this);
    }

    return $this;
  }

  /**
   * Retire un utilisateur de la liste des favoris
   *
   * @param User $user L'utilisateur à retirer
   * @return static L'instance de la boulangerie
   */
  public function removeFavoriteByUser(User $user): static {
    if ($this->favoriteByUsers->removeElement($user)) {
      $user->removeFavoriteBakery($this);
    }

    return $this;
  }

  /**
   * Vérifie si un utilisateur a mis la boulangerie en favori
   *
   * @param User $user L'utilisateur à vérifier
   * @return bool true si la boulangerie est un favori de l'utilisateur, false sinon
   */
  public function isFavoriteByUser(User $user): bool {
    return $this->favoriteByUsers->contains($user);
  }

  /**
   * Récupère la date de création de la boulangerie
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création de la boulangerie
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance de la boulangerie
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour de la boulangerie
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour de la boulangerie
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance de la boulangerie
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Récupère les boulangers qui gèrent la boulangerie
   *
   * @return Collection<int, User> Collection d'objets User
   */
  public function getBakers(): Collection {
    return $this->bakers;
  }

  /**
   * Ajoute un boulanger à la boulangerie
   *
   * Attribue automatiquement le rôle ROLE_BAKER à l'utilisateur.
   *
   * @param User $user L'utilisateur à ajouter comme boulanger
   * @return self L'instance de la boulangerie
   */
  public function addBaker(User $user): self {
    if (!$this->bakers->contains($user)) {
      $this->bakers[] = $user;
      $user->setManagedBakery($this);

      // Attribution du rôle ROLE_BAKER si nécessaire
      $roles = $user->getRoles();
      if (!in_array('ROLE_BAKER', $roles)) {
        $roles[] = 'ROLE_BAKER';
        $user->setRoles($roles);
      }
    }

    return $this;
  }

  /**
   * Retire un boulanger de la boulangerie
   *
   * Retire également le rôle ROLE_BAKER de l'utilisateur.
   *
   * @param User $user L'utilisateur à retirer comme boulanger
   * @return self L'instance de la boulangerie
   */
  public function removeBaker(User $user): self {
    if ($this->bakers->removeElement($user)) {
      if ($user->getManagedBakery() === $this) {
        $user->setManagedBakery(null);

        // Suppression du rôle ROLE_BAKER
        $roles = array_filter($user->getRoles(), function ($role) {
          return $role !== 'ROLE_BAKER';
        });

        $user->setRoles($roles);
      }
    }

    return $this;
  }

  /**
   * Récupère les ingrédients de la boulangerie
   *
   * @return Collection<int, Ingredient> Collection d'objets Ingredient
   */
  public function getIngredients(): Collection {
    return $this->ingredients;
  }

  /**
   * Ajoute un ingrédient à la boulangerie
   *
   * @param Ingredient $ingredient L'ingrédient à ajouter
   * @return static L'instance de la boulangerie
   */
  public function addIngredient(Ingredient $ingredient): static {
    if (!$this->ingredients->contains($ingredient)) {
      $this->ingredients->add($ingredient);
      $ingredient->setBakery($this);
    }

    return $this;
  }

  /**
   * Retire un ingrédient de la boulangerie
   *
   * @param Ingredient $ingredient L'ingrédient à retirer
   * @return static L'instance de la boulangerie
   */
  public function removeIngredient(Ingredient $ingredient): static {
    if ($this->ingredients->removeElement($ingredient)) {
      if ($ingredient->getBakery() === $this) {
        $ingredient->setBakery(null);
      }
    }

    return $this;
  }
}