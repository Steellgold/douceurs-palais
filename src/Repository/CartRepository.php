<?php

namespace App\Repository;

use App\Entity\Cart;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Cart
 *
 * Ce repository permet de gérer les paniers en base de données,
 * avec des méthodes spécifiques pour la recherche des paniers.
 *
 * @extends ServiceEntityRepository<Cart>
 *
 * @method Cart|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cart|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cart[]    findAll()
 * @method Cart[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CartRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Cart::class);
  }

  /**
   * Recherche un panier par son identifiant de session
   *
   * Utilisé pour retrouver le panier d'un utilisateur non connecté.
   *
   * @param string $sessionId L'identifiant de session
   * @return Cart|null Le panier correspondant ou null si non trouvé
   */
  public function findBySessionId(string $sessionId): ?Cart {
    return $this->findOneBy(['sessionId' => $sessionId]);
  }

  /**
   * Recherche le panier actif d'un utilisateur
   *
   * Récupère le panier le plus récent de l'utilisateur.
   *
   * @param User $user L'utilisateur dont on veut le panier
   * @return Cart|null Le panier actif ou null si aucun
   */
  public function findActiveCartByUser(User $user): ?Cart {
    return $this->createQueryBuilder('c')
      ->andWhere('c.user = :user')
      ->setParameter('user', $user)
      ->orderBy('c.createdAt', 'DESC')
      ->setMaxResults(1)
      ->getQuery()
      ->getOneOrNullResult();
  }

  /**
   * Enregistre un panier en base de données
   *
   * @param Cart $cart Le panier à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(Cart $cart, bool $flush = false): void {
    $this->getEntityManager()->persist($cart);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime un panier de la base de données
   *
   * @param Cart $cart Le panier à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(Cart $cart, bool $flush = false): void {
    $this->getEntityManager()->remove($cart);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }
}