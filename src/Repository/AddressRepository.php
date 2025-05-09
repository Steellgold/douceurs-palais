<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Address>
 *
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[]    findAll()
 * @method Address[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository {
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Address::class);
  }

  public function save(Address $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  public function remove(Address $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * @return Address[] Returns an array of Address objects
   */
  public function findByUser(User $user): array {
    return $this->createQueryBuilder('a')
      ->andWhere('a.user = :user')
      ->setParameter('user', $user)
      ->orderBy('a.isPrimary', 'DESC')
      ->addOrderBy('a.createdAt', 'DESC')
      ->getQuery()
      ->getResult();
  }

  public function setPrimaryAddress(Address $address): void {
    if ($address->isIsPrimary()) {
      return;
    }

    $this->createQueryBuilder('a')
      ->update()
      ->set('a.isPrimary', ':isPrimary')
      ->where('a.user = :user')
      ->setParameter('isPrimary', false)
      ->setParameter('user', $address->getUser())
      ->getQuery()
      ->execute();

    $address->setIsPrimary(true);
    $this->save($address, true);
  }

  public function findPrimaryAddressByUser(User $user): ?Address {
    return $this->createQueryBuilder('a')
      ->andWhere('a.user = :user')
      ->andWhere('a.isPrimary = :isPrimary')
      ->setParameter('user', $user)
      ->setParameter('isPrimary', true)
      ->getQuery()
      ->getOneOrNullResult();
  }

  public function hasPrimaryAddress(User $user): bool {
    $count = $this->createQueryBuilder('a')
      ->select('COUNT(a.id)')
      ->andWhere('a.user = :user')
      ->andWhere('a.isPrimary = :isPrimary')
      ->setParameter('user', $user)
      ->setParameter('isPrimary', true)
      ->getQuery()
      ->getSingleScalarResult();

    return $count > 0;
  }

  public function ensurePrimaryAddress(User $user): ?Address {
    if ($this->hasPrimaryAddress($user)) {
      return $this->findPrimaryAddressByUser($user);
    }

    $address = $this->createQueryBuilder('a')
      ->andWhere('a.user = :user')
      ->setParameter('user', $user)
      ->orderBy('a.createdAt', 'ASC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();

    if ($address) {
      $address->setIsPrimary(true);
      $this->save($address, true);
      return $address;
    }

    return null;
  }
}