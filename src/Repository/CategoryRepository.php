<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Category>
 *
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository {
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Category::class);
  }

  /**
   * @return Category[] Returns an array of Category objects ordered by position
   */
  public function findAllOrdered(): array {
    return $this->createQueryBuilder('c')
      ->orderBy('c.position', 'ASC')
      ->getQuery()
      ->getResult();
  }

  public function findBySlug(string $slug): ?Category {
    return $this->findOneBy(['slug' => $slug]);
  }

  /**
   * Find categories with product counts
   *
   * @return array Returns an array of categories with product counts
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
   * Find categories with products for a specific bakery
   *
   * @param string $bakeryId The bakery ID
   * @return array Returns an array of categories with products for the bakery
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

  public function save(Category $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  public function remove(Category $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }
}