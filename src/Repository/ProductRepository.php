<?php

namespace App\Repository;

use App\Entity\Bakery;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Product
 *
 * Ce repository permet de gérer les produits en base de données,
 * avec des méthodes spécifiques pour la recherche et le filtrage des produits.
 *
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Product::class);
  }

  /**
   * Recherche un produit par son slug
   *
   * @param string $slug Le slug du produit
   * @return Product|null Le produit correspondant ou null si non trouvé
   */
  public function findOneBySlug(string $slug): ?Product {
    return $this->findOneBy(['slug' => $slug]);
  }

  /**
   * Récupère les produits les plus récents
   *
   * @param int $limit Nombre maximum de produits à retourner
   * @return Product[] Tableau des produits les plus récents
   */
  public function findLatestProducts(int $limit = 10): array {
    return $this->createQueryBuilder('p')
      ->orderBy('p.createdAt', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les produits d'une boulangerie
   *
   * @param Bakery $bakery La boulangerie dont on veut les produits
   * @return Product[] Tableau des produits de la boulangerie
   */
  public function findByBakery(Bakery $bakery): array {
    return $this->createQueryBuilder('p')
      ->andWhere('p.bakery = :bakery')
      ->setParameter('bakery', $bakery)
      ->orderBy('p.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les produits d'une catégorie
   *
   * @param Category $category La catégorie dont on veut les produits
   * @param int|null $limit Nombre maximum de produits à retourner (null pour tous)
   * @return Product[] Tableau des produits de la catégorie
   */
  public function findByCategory(Category $category, int|null $limit): array {
    $qb = $this->createQueryBuilder('p')
      ->andWhere('p.category = :category')
      ->setParameter('category', $category)
      ->orderBy('p.name', 'ASC');

    if ($limit) {
      $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
  }

  /**
   * Récupère les produits d'une catégorie avec pagination
   *
   * @param Category $category La catégorie dont on veut les produits
   * @param int $page Le numéro de page (commençant à 1)
   * @param int $limit Nombre de produits par page
   * @return Paginator Objet de pagination des produits
   */
  public function findByCategoryPaginated(Category $category, int $page = 1, int $limit = 12) {
    $firstResult = ($page - 1) * $limit;

    $query = $this->createQueryBuilder('p')
      ->andWhere('p.category = :category')
      ->setParameter('category', $category)
      ->orderBy('p.name', 'ASC')
      ->setFirstResult($firstResult)
      ->setMaxResults($limit)
      ->getQuery();

    return new Paginator($query, true);
  }

  /**
   * Récupère les produits d'une boulangerie et d'une catégorie spécifiques
   *
   * @param Bakery $bakery La boulangerie dont on veut les produits
   * @param Category $category La catégorie dont on veut les produits
   * @return Product[] Tableau des produits correspondants
   */
  public function findByBakeryAndCategory(Bakery $bakery, Category $category): array {
    return $this->createQueryBuilder('p')
      ->andWhere('p.bakery = :bakery')
      ->andWhere('p.category = :category')
      ->setParameter('bakery', $bakery)
      ->setParameter('category', $category)
      ->orderBy('p.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les produits les plus populaires d'une boulangerie
   *
   * @param Bakery $bakery La boulangerie dont on veut les produits populaires
   * @param int $limit Nombre maximum de produits à retourner
   * @return Product[] Tableau des produits populaires
   */
  public function findMostPopularByBakery(Bakery $bakery, int $limit = 3): array {
    return $this->createQueryBuilder('p')
      ->andWhere('p.bakery = :bakery')
      ->setParameter('bakery', $bakery)
      ->orderBy('p.popularity', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les produits les plus populaires
   *
   * @param int $limit Nombre maximum de produits à retourner
   * @return Product[] Tableau des produits populaires
   */
  public function findMostPopular(int $limit = 4): array {
    return $this->createQueryBuilder('p')
      ->orderBy('p.popularity', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les produits des boulangeries favorites d'un utilisateur
   *
   * @param User $user L'utilisateur dont on veut les produits des boulangeries favorites
   * @param int $limit Nombre maximum de produits à retourner
   * @return Product[] Tableau des produits des boulangeries favorites
   */
  public function findFromFavoriteBakeries(User $user, int $limit): array {
    $qb = $this->createQueryBuilder('p')
      ->join('p.bakery', 'b')
      ->join('b.favoriteByUsers', 'u')
      ->where('u.id = :userId')
      ->setParameter('userId', $user->getId())
      ->orderBy('p.name', 'ASC');

    if ($limit) {
      $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
  }

  /**
   * Récupère les produits dans une fourchette de prix
   *
   * @param float $minPrice Prix minimum
   * @param float $maxPrice Prix maximum
   * @return Product[] Tableau des produits correspondants
   */
  public function findByPriceRange(float $minPrice, float $maxPrice): array {
    return $this->createQueryBuilder('p')
      ->andWhere('p.price >= :min')
      ->andWhere('p.price <= :max')
      ->setParameter('min', $minPrice)
      ->setParameter('max', $maxPrice)
      ->orderBy('p.price', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les produits contenant un ingrédient spécifique
   *
   * @param string $ingredient L'ingrédient à rechercher
   * @return Product[] Tableau des produits correspondants
   */
  public function findByIngredient(string $ingredient): array {
    return $this->createQueryBuilder('p')
      ->andWhere('JSON_CONTAINS(p.ingredients, :ingredient) = 1')
      ->setParameter('ingredient', json_encode($ingredient))
      ->getQuery()
      ->getResult();
  }

  /**
   * Recherche des produits par nom
   *
   * Utilise LIKE pour rechercher des correspondances partielles.
   *
   * @param string $term Le terme de recherche
   * @return Product[] Tableau des produits correspondants
   */
  public function searchByName(string $term): array {
    return $this->createQueryBuilder('p')
      ->andWhere('p.name LIKE :term')
      ->setParameter('term', '%' . $term . '%')
      ->orderBy('p.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Enregistre un produit en base de données
   *
   * @param Product $entity Le produit à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(Product $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime un produit de la base de données
   *
   * @param Product $entity Le produit à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(Product $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Récupère les produits disponibles avec des points de fidélité pour une boulangerie
   *
   * @param Bakery $bakery La boulangerie dont on veut les produits
   * @return Product[] Tableau des produits disponibles avec des points
   */
  public function findAvailableWithPointsByBakery(Bakery $bakery): array {
    return $this->createQueryBuilder('p')
      ->andWhere('p.bakery = :bakery')
      ->andWhere('p.requiredPoints IS NOT NULL')
      ->setParameter('bakery', $bakery)
      ->orderBy('p.requiredPoints', 'ASC')
      ->getQuery()
      ->getResult();
  }
}