<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité représentant une adresse
 *
 * Cette classe représente une adresse postale qui peut être utilisée
 * comme adresse de livraison ou de facturation. Chaque adresse est
 * associée à un utilisateur et peut être marquée comme principale.
 */
#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Address {
  /**
   * Identifiant unique de l'adresse (UUID)
   */
  #[ORM\Id]
  #[ORM\Column(type: 'string', length: 36)]
  private ?string $id = null;

  /**
   * Rue de l'adresse
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'Veuillez entrer une adresse')]
  private ?string $street = null;

  /**
   * Code postal de l'adresse
   */
  #[ORM\Column(length: 10)]
  #[Assert\NotBlank(message: 'Veuillez entrer un code postal')]
  #[Assert\Regex(pattern: '/^[0-9]{5}$/', message: 'Ce code postal n\'est pas valide')]
  private ?string $postalCode = null;

  /**
   * Ville de l'adresse
   */
  #[ORM\Column(length: 255)]
  #[Assert\NotBlank(message: 'Veuillez entrer une ville')]
  private ?string $city = null;

  /**
   * Complément d'adresse (appartement, étage, etc.)
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $complement = null;

  /**
   * Indique si cette adresse est l'adresse principale de l'utilisateur
   */
  #[ORM\Column]
  private ?bool $isPrimary = false;

  /**
   * Libellé personnalisé de l'adresse (ex: "Domicile", "Bureau")
   */
  #[ORM\Column(length: 255, nullable: true)]
  private ?string $label = null;

  /**
   * Utilisateur propriétaire de l'adresse
   */
  #[ORM\ManyToOne(inversedBy: 'addresses')]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $user = null;

  /**
   * Date de création de l'adresse
   */
  #[ORM\Column]
  private ?\DateTimeImmutable $createdAt = null;

  /**
   * Date de dernière mise à jour de l'adresse
   */
  #[ORM\Column(nullable: true)]
  private ?\DateTimeImmutable $updatedAt = null;

  /**
   * Constructeur de l'adresse
   *
   * Initialise une nouvelle adresse avec un UUID et une date de création.
   */
  public function __construct() {
    $this->id = Uuid::v4()->toRfc4122();
    $this->createdAt = new \DateTimeImmutable();
  }

  /**
   * Met à jour la date de mise à jour lors de la modification de l'adresse
   *
   * Cette méthode est automatiquement appelée par Doctrine avant chaque mise à jour.
   */
  #[ORM\PreUpdate]
  public function setUpdatedAtValue(): void {
    $this->updatedAt = new \DateTimeImmutable();
  }

  /**
   * Récupère l'identifiant de l'adresse
   *
   * @return string|null L'identifiant UUID
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * Récupère la rue de l'adresse
   *
   * @return string|null La rue
   */
  public function getStreet(): ?string {
    return $this->street;
  }

  /**
   * Définit la rue de l'adresse
   *
   * @param string $street La nouvelle rue
   * @return static L'instance de l'adresse
   */
  public function setStreet(string $street): static {
    $this->street = $street;

    return $this;
  }

  /**
   * Récupère le code postal de l'adresse
   *
   * @return string|null Le code postal
   */
  public function getPostalCode(): ?string {
    return $this->postalCode;
  }

  /**
   * Définit le code postal de l'adresse
   *
   * @param string $postalCode Le nouveau code postal
   * @return static L'instance de l'adresse
   */
  public function setPostalCode(string $postalCode): static {
    $this->postalCode = $postalCode;

    return $this;
  }

  /**
   * Récupère la ville de l'adresse
   *
   * @return string|null La ville
   */
  public function getCity(): ?string {
    return $this->city;
  }

  /**
   * Définit la ville de l'adresse
   *
   * @param string $city La nouvelle ville
   * @return static L'instance de l'adresse
   */
  public function setCity(string $city): static {
    $this->city = $city;

    return $this;
  }

  /**
   * Récupère le complément d'adresse
   *
   * @return string|null Le complément d'adresse
   */
  public function getComplement(): ?string {
    return $this->complement;
  }

  /**
   * Définit le complément d'adresse
   *
   * @param string|null $complement Le nouveau complément d'adresse
   * @return static L'instance de l'adresse
   */
  public function setComplement(?string $complement): static {
    $this->complement = $complement;

    return $this;
  }

  /**
   * Vérifie si l'adresse est l'adresse principale
   *
   * @return bool|null true si l'adresse est principale, false sinon
   */
  public function isIsPrimary(): ?bool {
    return $this->isPrimary;
  }

  /**
   * Définit si l'adresse est l'adresse principale
   *
   * @param bool $isPrimary Le nouveau statut d'adresse principale
   * @return static L'instance de l'adresse
   */
  public function setIsPrimary(bool $isPrimary): static {
    $this->isPrimary = $isPrimary;

    return $this;
  }

  /**
   * Récupère le libellé de l'adresse
   *
   * @return string|null Le libellé
   */
  public function getLabel(): ?string {
    return $this->label;
  }

  /**
   * Définit le libellé de l'adresse
   *
   * @param string|null $label Le nouveau libellé
   * @return static L'instance de l'adresse
   */
  public function setLabel(?string $label): static {
    $this->label = $label;

    return $this;
  }

  /**
   * Récupère l'utilisateur propriétaire de l'adresse
   *
   * @return User|null L'utilisateur
   */
  public function getUser(): ?User {
    return $this->user;
  }

  /**
   * Définit l'utilisateur propriétaire de l'adresse
   *
   * @param User|null $user Le nouvel utilisateur
   * @return static L'instance de l'adresse
   */
  public function setUser(?User $user): static {
    $this->user = $user;

    return $this;
  }

  /**
   * Récupère la date de création de l'adresse
   *
   * @return \DateTimeImmutable|null La date de création
   */
  public function getCreatedAt(): ?\DateTimeImmutable {
    return $this->createdAt;
  }

  /**
   * Définit la date de création de l'adresse
   *
   * @param \DateTimeImmutable $createdAt La nouvelle date de création
   * @return static L'instance de l'adresse
   */
  public function setCreatedAt(\DateTimeImmutable $createdAt): static {
    $this->createdAt = $createdAt;

    return $this;
  }

  /**
   * Récupère la date de dernière mise à jour de l'adresse
   *
   * @return \DateTimeImmutable|null La date de mise à jour
   */
  public function getUpdatedAt(): ?\DateTimeImmutable {
    return $this->updatedAt;
  }

  /**
   * Définit la date de dernière mise à jour de l'adresse
   *
   * @param \DateTimeImmutable|null $updatedAt La nouvelle date de mise à jour
   * @return static L'instance de l'adresse
   */
  public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static {
    $this->updatedAt = $updatedAt;

    return $this;
  }

  /**
   * Génère l'adresse complète au format texte
   *
   * @return string L'adresse complète formatée
   */
  public function getFullAddress(): string {
    $address = $this->street;

    if ($this->complement) {
      $address .= ', ' . $this->complement;
    }

    $address .= ', ' . $this->postalCode . ' ' . $this->city;

    return $address;
  }
}