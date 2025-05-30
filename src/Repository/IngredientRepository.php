<?php

namespace App\Repository;

use App\Entity\Bakery;
use App\Entity\Ingredient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour l'entité Ingredient
 *
 * Ce repository permet de gérer les ingrédients en base de données,
 * avec des méthodes spécifiques pour la recherche et la manipulation des ingrédients.
 *
 * @extends ServiceEntityRepository<Ingredient>
 *
 * @method Ingredient|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ingredient|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ingredient[]    findAll()
 * @method Ingredient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class IngredientRepository extends ServiceEntityRepository {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, Ingredient::class);
  }

  /**
   * Enregistre un ingrédient en base de données
   *
   * @param Ingredient $entity L'ingrédient à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(Ingredient $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);
    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime un ingrédient de la base de données
   *
   * @param Ingredient $entity L'ingrédient à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(Ingredient $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);
    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Récupère les ingrédients d'une boulangerie
   *
   * @param Bakery $bakery La boulangerie dont on veut les ingrédients
   * @return Ingredient[] Tableau des ingrédients de la boulangerie
   */
  public function findByBakery(Bakery $bakery): array {
    return $this->createQueryBuilder('i')
      ->andWhere('i.bakery = :bakery')
      ->setParameter('bakery', $bakery)
      ->orderBy('i.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Recherche des ingrédients par nom pour une boulangerie spécifique
   *
   * @param Bakery $bakery La boulangerie dont on veut les ingrédients
   * @param string $term Le terme de recherche
   * @return Ingredient[] Tableau des ingrédients correspondants
   */
  public function searchByNameAndBakery(Bakery $bakery, string $term): array {
    return $this->createQueryBuilder('i')
      ->andWhere('i.bakery = :bakery')
      ->andWhere('i.name LIKE :term')
      ->setParameter('bakery', $bakery)
      ->setParameter('term', '%' . $term . '%')
      ->orderBy('i.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Récupère les ingrédients végans d'une boulangerie
   *
   * @param Bakery $bakery La boulangerie dont on veut les ingrédients végans
   * @return Ingredient[] Tableau des ingrédients végans de la boulangerie
   */
  public function findVeganByBakery(Bakery $bakery): array {
    return $this->createQueryBuilder('i')
      ->andWhere('i.bakery = :bakery')
      ->andWhere('i.isVegan = :isVegan')
      ->setParameter('bakery', $bakery)
      ->setParameter('isVegan', true)
      ->orderBy('i.name', 'ASC')
      ->getQuery()
      ->getResult();
  }

  /**
   * Vérifie si le nom d'un ingrédient existe déjà pour une boulangerie
   *
   * @param string $name Le nom à vérifier
   * @param Bakery $bakery La boulangerie à vérifier
   * @param string|null $excludeId ID d'un ingrédient à exclure (pour les mises à jour)
   * @return bool true si le nom existe déjà, false sinon
   */
  public function nameExistsForBakery(string $name, Bakery $bakery, ?string $excludeId = null): bool {
    $qb = $this->createQueryBuilder('i')
      ->select('COUNT(i.id)')
      ->andWhere('i.bakery = :bakery')
      ->andWhere('LOWER(i.name) = LOWER(:name)')
      ->setParameter('bakery', $bakery)
      ->setParameter('name', $name);

    if ($excludeId) {
      $qb->andWhere('i.id != :excludeId')
        ->setParameter('excludeId', $excludeId);
    }

    return (int)$qb->getQuery()->getSingleScalarResult() > 0;
  }
}