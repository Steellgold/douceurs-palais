<?php

namespace App\Repository;

use App\Entity\Address;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Address
 *
 * Ce repository permet de gérer les adresses en base de données,
 * avec des méthodes spécifiques pour la recherche et la manipulation des adresses.
 *
 * @extends ServiceEntityRepository<Address>
 *
 * @method Address|null find($id, $lockMode = null, $lockVersion = null)
 * @method Address|null findOneBy(array $criteria, array $orderBy = null)
 * @method Address[]    findAll()
 * @method Address[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AddressRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Address::class);
  }

  /**
   * Enregistre une adresse en base de données
   *
   * @param Address $entity L'adresse à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(Address $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime une adresse de la base de données
   *
   * @param Address $entity L'adresse à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(Address $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Récupère toutes les adresses d'un utilisateur
   *
   * Les adresses sont triées avec l'adresse principale en premier,
   * puis par date de création décroissante.
   *
   * @param User $user L'utilisateur dont on veut récupérer les adresses
   * @return Address[] Tableau des adresses de l'utilisateur
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

  /**
   * Définit une adresse comme adresse principale
   *
   * Désactive d'abord toutes les autres adresses principales de l'utilisateur,
   * puis active l'adresse spécifiée comme adresse principale.
   *
   * @param Address $address L'adresse à définir comme principale
   * @return void
   */
  public function setPrimaryAddress(Address $address): void {
    if ($address->isIsPrimary()) {
      return;
    }

    // Désactive toutes les adresses principales de l'utilisateur
    $this->createQueryBuilder('a')
      ->update()
      ->set('a.isPrimary', ':isPrimary')
      ->where('a.user = :user')
      ->setParameter('isPrimary', false)
      ->setParameter('user', $address->getUser())
      ->getQuery()
      ->execute();

    // Active l'adresse spécifiée comme adresse principale
    $address->setIsPrimary(true);
    $this->save($address, true);
  }

  /**
   * Recherche l'adresse principale d'un utilisateur
   *
   * @param User $user L'utilisateur dont on veut l'adresse principale
   * @return Address|null L'adresse principale ou null si aucune
   */
  public function findPrimaryAddressByUser(User $user): ?Address {
    return $this->createQueryBuilder('a')
      ->andWhere('a.user = :user')
      ->andWhere('a.isPrimary = :isPrimary')
      ->setParameter('user', $user)
      ->setParameter('isPrimary', true)
      ->getQuery()
      ->getOneOrNullResult();
  }

  /**
   * Vérifie si un utilisateur a une adresse principale
   *
   * @param User $user L'utilisateur à vérifier
   * @return bool true si l'utilisateur a une adresse principale, false sinon
   */
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

  /**
   * S'assure qu'un utilisateur a une adresse principale
   *
   * Si l'utilisateur n'a pas d'adresse principale, définit sa première adresse
   * comme principale.
   *
   * @param User $user L'utilisateur concerné
   * @return Address|null L'adresse principale ou null si aucune adresse
   */
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