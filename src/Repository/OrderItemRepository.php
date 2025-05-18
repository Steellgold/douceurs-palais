<?php

namespace App\Repository;

use App\Entity\OrderItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité OrderItem
 *
 * Ce repository permet de gérer les éléments de commande en base de données.
 *
 * @extends ServiceEntityRepository<OrderItem>
 *
 * @method OrderItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method OrderItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method OrderItem[]    findAll()
 * @method OrderItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderItemRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, OrderItem::class);
  }

  /**
   * Enregistre un élément de commande en base de données
   *
   * @param OrderItem $entity L'élément de commande à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(OrderItem $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime un élément de commande de la base de données
   *
   * @param OrderItem $entity L'élément de commande à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(OrderItem $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }
}