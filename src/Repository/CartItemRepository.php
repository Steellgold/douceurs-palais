<?php

namespace App\Repository;

use App\Entity\CartItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité CartItem
 *
 * Ce repository permet de gérer les éléments de panier en base de données.
 *
 * @extends ServiceEntityRepository<CartItem>
 *
 * @method CartItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CartItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method CartItem[]    findAll()
 * @method CartItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartItemRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, CartItem::class);
  }

  /**
   * Enregistre un élément de panier en base de données
   *
   * @param CartItem $entity L'élément de panier à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(CartItem $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime un élément de panier de la base de données
   *
   * @param CartItem $entity L'élément de panier à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(CartItem $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }
}