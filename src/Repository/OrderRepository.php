<?php

namespace App\Repository;

use App\Entity\Bakery;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Order
 *
 * Ce repository permet de gérer les commandes en base de données,
 * avec des méthodes spécifiques pour la recherche et le filtrage des commandes.
 *
 * @extends ServiceEntityRepository<Order>
 *
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Order::class);
  }

  /**
   * Enregistre une commande en base de données
   *
   * @param Order $entity La commande à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(Order $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime une commande de la base de données
   *
   * @param Order $entity La commande à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(Order $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Récupère les commandes d'un utilisateur
   *
   * Les commandes sont triées par date de création décroissante (les plus récentes d'abord).
   *
   * @param User $user L'utilisateur dont on veut les commandes
   * @return Order[] Tableau des commandes de l'utilisateur
   */
  public function findByUser(User $user): array {
    return $this->createQueryBuilder('o')
      ->andWhere('o.user = :user')
      ->setParameter('user', $user)
      ->orderBy('o.createdAt', 'DESC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les commandes d'une boulangerie
   *
   * @param Bakery $bakery La boulangerie dont on veut les commandes
   * @return Order[] Tableau des commandes de la boulangerie
   */
  public function findByBakery(Bakery $bakery): array {
    return $this->createQueryBuilder('o')
      ->andWhere('o.bakery = :bakery')
      ->setParameter('bakery', $bakery)
      ->orderBy('o.createdAt', 'DESC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les commandes contenant des produits d'une boulangerie spécifique
   *
   * Permet également de filtrer par statut.
   *
   * @param Bakery $bakery La boulangerie dont on veut les commandes
   * @param string|null $status Le statut des commandes à filtrer (null pour tous)
   * @return Order[] Tableau des commandes correspondantes
   */
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

  /**
   * Récupère les commandes en attente
   *
   * @return Order[] Tableau des commandes en attente
   */
  public function findPendingOrders(): array {
    return $this->createQueryBuilder('o')
      ->andWhere('o.status = :status')
      ->setParameter('status', Order::STATUS_PAYMENT_PROCESSING)
      ->getQuery()
      ->getResult();
  }

  /**
   * Recherche une commande par son identifiant de session Stripe
   *
   * @param string $sessionId L'identifiant de session Stripe
   * @return Order|null La commande correspondante ou null si non trouvée
   */
  public function findByStripeSessionId(string $sessionId): ?Order {
    return $this->createQueryBuilder('o')
      ->andWhere('o.stripeSessionId = :sessionId')
      ->setParameter('sessionId', $sessionId)
      ->getQuery()
      ->getOneOrNullResult();
  }

  /**
   * Recherche une commande par une partie de son identifiant d'intention de paiement Stripe
   *
   * Utilise LIKE pour rechercher des correspondances partielles.
   *
   * @param string $paymentIntentId L'identifiant d'intention de paiement Stripe (partiel)
   * @return Order|null La commande correspondante ou null si non trouvée
   * @throws Exception En cas d'erreur de base de données
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

  /**
   * Récupère les commandes les plus récentes
   *
   * @param int $limit Nombre maximum de commandes à retourner
   * @return Order[] Tableau des commandes les plus récentes
   */
  public function findLatest(int $limit = 10): array {
    return $this->createQueryBuilder('o')
      ->orderBy('o.createdAt', 'DESC')
      ->setMaxResults($limit)
      ->getQuery()
      ->getResult();
  }
}