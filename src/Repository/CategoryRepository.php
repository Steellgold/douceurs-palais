<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Category
 *
 * Ce repository permet de gérer les catégories en base de données,
 * avec des méthodes spécifiques pour la recherche et le filtrage des catégories.
 *
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Category::class);
  }

  /**
   * Récupère toutes les catégories triées par position
   *
   * @return Category[] Tableau des catégories triées
   */
  public function findAllOrdered(): array {
    return $this->createQueryBuilder('c')
      ->orderBy('c.position', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Recherche une catégorie par son slug
   *
   * @param string $slug Le slug de la catégorie
   * @return Category|null La catégorie correspondante ou null si non trouvée
   */
  public function findBySlug(string $slug): ?Category {
    return $this->findOneBy(['slug' => $slug]);
  }

  /**
   * Récupère les catégories avec le nombre de produits dans chacune
   *
   * @return array Tableau associatif des catégories avec leur nombre de produits
   */
  public function findWithProductCounts(): array {
    return $this->createQueryBuilder('c')
      ->select('c', 'COUNT(p.id) as productCount')
      ->leftJoin('c.products', 'p')
      ->groupBy('c.id')
      ->orderBy('c.position', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les catégories qui contiennent des produits d'une boulangerie spécifique
   *
   * @param string $bakeryId L'identifiant de la boulangerie
   * @return array Tableau associatif des catégories avec leur nombre de produits
   */
  public function findCategoriesWithProductsByBakery(string $bakeryId): array {
    return $this->createQueryBuilder('c')
      ->select('c', 'COUNT(p.id) as productCount')
      ->leftJoin('c.products', 'p')
      ->where('p.bakery = :bakeryId')
      ->setParameter('bakeryId', $bakeryId)
      ->groupBy('c.id')
      ->having('productCount > 0')
      ->orderBy('c.position', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Enregistre une catégorie en base de données
   *
   * @param Category $entity La catégorie à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(Category $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime une catégorie de la base de données
   *
   * @param Category $entity La catégorie à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(Category $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }
}