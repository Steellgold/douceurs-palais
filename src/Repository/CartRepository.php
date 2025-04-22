<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cart>
 *
 * @method Cart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cart[]    findAll()
 * @method Cart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartRepository extends ServiceEntityRepository {
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Cart::class);
  }

  public function findBySessionId(string $sessionId): ?Cart {
    return $this->findOneBy(['sessionId' => $sessionId]);
  }

  public function findActiveCartByUser(User $user): ?Cart {
    return $this->createQueryBuilder('c')
      ->andWhere('c.user = :user')
      ->setParameter('user', $user)
      ->orderBy('c.createdAt', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }

  public function save(Cart $cart, bool $flush = false): void {
    $this->getEntityManager()->persist($cart);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  public function remove(Cart $cart, bool $flush = false): void {
    $this->getEntityManager()->remove($cart);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }
}