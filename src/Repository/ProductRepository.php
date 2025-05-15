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
  public function findByBakery(Bakery $bakery): array {
    return $this->createQueryBuilder('p')
      ->andWhere('p.bakery = :bakery')
      ->setParameter('bakery', $bakery)
      ->orderBy('p.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

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
   * @return Product[] Returns an array of Product objects
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
   * @return Product[] Returns an array of most popular Product objects
   */
  public function findMostPopular(int $limit = 4): array {
    return $this->createQueryBuilder('p')
      ->orderBy('p.popularity', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  /**
   * @return Product[] Returns an array of Product objects from favorite bakeries
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

  public function save(Product $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  public function remove(Product $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

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