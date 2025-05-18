<?php

namespace App\Repository;

use App\Entity\Bakery;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Bakery
 *
 * Ce repository permet de gérer les boulangeries en base de données,
 * avec des méthodes spécifiques pour la recherche et la manipulation des boulangeries.
 *
 * @extends ServiceEntityRepository<Bakery>
 *
 * @method Bakery|null find($id, $lockMode = null, $lockVersion = null)
 * @method Bakery|null findOneBy(array $criteria, array $orderBy = null)
 * @method Bakery[]    findAll()
 * @method Bakery[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BakeryRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Bakery::class);
  }

  /**
   * Recherche une boulangerie par son slug
   *
   * @param string $slug Le slug de la boulangerie
   * @return Bakery|null La boulangerie correspondante ou null si non trouvée
   */
  public function findBySlug(string $slug): ?Bakery {
    return $this->findOneBy(['slug' => $slug]);
  }

  /**
   * Récupère les boulangeries les plus populaires
   *
   * La popularité est déterminée par le nombre d'utilisateurs qui ont
   * mis la boulangerie en favori.
   *
   * @param int $limit Nombre maximum de boulangeries à retourner
   * @return Bakery[] Tableau des boulangeries populaires
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
   * Récupère les boulangeries favorites d'un utilisateur
   *
   * @param User $user L'utilisateur dont on veut récupérer les boulangeries favorites
   * @param int|null $limit Nombre maximum de boulangeries à retourner (null pour toutes)
   * @return Bakery[] Tableau des boulangeries favorites
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

  /**
   * Compte le nombre de boulangeries favorites d'un utilisateur
   *
   * @param User $user L'utilisateur dont on veut compter les boulangeries favorites
   * @return int Le nombre de boulangeries favorites
   */
  public function countFavoritesByUser(User $user): int {
    return $this->createQueryBuilder('b')
      ->select('COUNT(b.id)')
      ->innerJoin('b.favoriteByUsers', 'u')
      ->where('u.id = :userId')
      ->setParameter('userId', $user->getId())
      ->getQuery()
      ->getSingleScalarResult();
  }

  /**
   * Enregistre une boulangerie en base de données
   *
   * @param Bakery $entity La boulangerie à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(Bakery $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime une boulangerie de la base de données
   *
   * @param Bakery $entity La boulangerie à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(Bakery $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Récupère toutes les boulangeries triées par nom
   *
   * @return Bakery[] Tableau des boulangeries triées par nom
   */
  public function findAllOrderedByName(): array {
    return $this->createQueryBuilder('b')
      ->orderBy('b.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère toutes les boulangeries avec le nombre de boulangers
   *
   * @return array Tableau associatif des boulangeries avec leur nombre de boulangers
   */
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