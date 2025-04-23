<?php

namespace App\Repository;

use App\Entity\Bakery;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Bakery>
 *
 * @method Bakery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bakery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bakery[]    findAll()
 * @method Bakery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BakeryRepository extends ServiceEntityRepository {
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Bakery::class);
  }

  public function findBySlug(string $slug): ?Bakery {
    return $this->findOneBy(['slug' => $slug]);
  }

  /**
   * @return Bakery[] Returns an array of Bakery objects
   */
  public function findPopularBakeries(int $limit = 3): array {
    return $this->createQueryBuilder('b')
      ->leftJoin('b.favoriteByUsers', 'u')
      ->groupBy('b.id')
      ->orderBy('COUNT(u.id)', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }

  /**
   * @return Bakery[] Returns an array of Bakery objects
   */
  public function findFavoritesByUser(User $user, int|null $limit): array {
    $qb = $this->createQueryBuilder('b')
      ->innerJoin('b.favoriteByUsers', 'u')
      ->where('u.id = :userId')
      ->setParameter('userId', $user->getId())
      ->orderBy('b.name', 'ASC');

    if ($limit) {
      $qb->setMaxResults($limit);
    }

    return $qb->getQuery()->getResult();
  }

  public function countFavoritesByUser(User $user): int {
    return $this->createQueryBuilder('b')
      ->select('COUNT(b.id)')
      ->innerJoin('b.favoriteByUsers', 'u')
      ->where('u.id = :userId')
      ->setParameter('userId', $user->getId())
      ->getQuery()
      ->getSingleScalarResult();
  }

  public function save(Bakery $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  public function remove(Bakery $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  public function findAllOrderedByName(): array {
    return $this->createQueryBuilder('b')
      ->orderBy('b.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  public function findBakeriesWithBakerCounts(): array {
    return $this->createQueryBuilder('b')
      ->select('b', 'COUNT(u.id) as bakerCount')
      ->leftJoin('b.bakers', 'u')
      ->groupBy('b.id')
      ->orderBy('b.name', 'ASC')
      ->getQuery()
      ->getResult();
  }
}