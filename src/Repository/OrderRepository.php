<?php

namespace App\Repository;

use App\Entity\Bakery;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository {
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Order::class);
  }

  public function save(Order $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  public function remove(Order $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * @return Order[] Returns an array of Order objects
   */
  public function findByUser(User $user): array {
    return $this->createQueryBuilder('o')
      ->andWhere('o.user = :user')
      ->setParameter('user', $user)
      ->orderBy('o.createdAt', 'DESC')
      ->getQuery()
      ->getResult();
  }

  public function findByBakery(Bakery $bakery): array {
    return $this->createQueryBuilder('o')
      ->andWhere('o.bakery = :bakery')
      ->setParameter('bakery', $bakery)
      ->orderBy('o.createdAt', 'DESC')
      ->getQuery()
      ->getResult();
  }

  public function findOrdersContainingBakeryProducts(Bakery $bakery, ?string $status = null): array {
    $qb = $this->createQueryBuilder('o')
      ->join('o.items', 'oi')
      ->join('oi.product', 'p')
      ->where('p.bakery = :bakery')
      ->setParameter('bakery', $bakery)
      ->orderBy('o.createdAt', 'DESC')
      ->distinct();

    if ($status && $status !== 'all') {
      $qb->andWhere('o.status = :status')
        ->setParameter('status', $status);
    }

    return $qb->getQuery()->getResult();
  }

  public function findPendingOrders(): array {
    return $this->createQueryBuilder('o')
      ->andWhere('o.status = :status')
      ->setParameter('status', Order::STATUS_PAYMENT_PROCESSING)
      ->getQuery()
      ->getResult();
  }

  public function findByStripeSessionId(string $sessionId): ?Order {
    return $this->createQueryBuilder('o')
      ->andWhere('o.stripeSessionId = :sessionId')
      ->setParameter('sessionId', $sessionId)
      ->getQuery()
      ->getOneOrNullResult();
  }

  /**
   * @throws Exception
   */
  public function findByPartialPaymentIntentId(string $paymentIntentId): ?Order {
    $conn = $this->getEntityManager()->getConnection();
    $sql = 'SELECT id FROM `order` WHERE stripe_payment_intent_id LIKE :paymentId LIMIT 1';
    $result = $conn->executeQuery($sql, ['paymentId' => '%' . $paymentIntentId . '%'])->fetchAssociative();

    if ($result && isset($result['id'])) {
      return $this->find($result['id']);
    }

    return null;
  }

  public function findLatest(int $limit = 10): array {
    return $this->createQueryBuilder('o')
      ->orderBy('o.createdAt', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }
}