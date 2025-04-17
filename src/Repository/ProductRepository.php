<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository {
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Product::class);
  }

  public function findOneBySlug(string $slug): ?Product {
    return $this->findOneBy(['slug' => $slug]);
  }

  /**
   * @return Product[] Returns an array of Product objects
   */
  public function findLatestProducts(int $limit = 10): array {
    return $this->createQueryBuilder('p')
      ->orderBy('p.createdAt', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  /**
   * @return Product[] Returns an array of Product objects
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
   * @return Product[] Returns an array of Product objects
   */
  public function findByIngredient(string $ingredient): array {
    return $this->createQueryBuilder('p')
      ->andWhere('JSON_CONTAINS(p.ingredients, :ingredient) = 1')
      ->setParameter('ingredient', json_encode($ingredient))
      ->getQuery()
      ->getResult();
  }

  /**
   * @return Product[] Returns an array of Product objects
   */
  public function searchByName(string $term): array {
    return $this->createQueryBuilder('p')
      ->andWhere('p.name LIKE :term')
      ->setParameter('term', '%' . $term . '%')
      ->orderBy('p.name', 'ASC')
      ->getQuery()
      ->getResult();
  }
}