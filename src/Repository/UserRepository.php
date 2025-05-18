<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * Repository pour l'entité User
 *
 * Ce repository permet de gérer les utilisateurs en base de données,
 * avec des méthodes spécifiques pour la recherche et la mise à jour des utilisateurs.
 * Implémente PasswordUpgraderInterface pour la mise à jour automatique des mots de passe.
 *
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface {
  /**
   * Constructeur du repository
   *
   * @param ManagerRegistry $registry Registre des gestionnaires d'entités
   */
  public function __construct(ManagerRegistry $registry) {
    parent::__construct($registry, User::class);
  }

  /**
   * Enregistre un utilisateur en base de données
   *
   * @param User $entity L'utilisateur à sauvegarder
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function save(User $entity, bool $flush = false): void {
    $this->getEntityManager()->persist($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Supprime un utilisateur de la base de données
   *
   * @param User $entity L'utilisateur à supprimer
   * @param bool $flush Si true, exécute immédiatement la requête
   * @return void
   */
  public function remove(User $entity, bool $flush = false): void {
    $this->getEntityManager()->remove($entity);

    if ($flush) {
      $this->getEntityManager()->flush();
    }
  }

  /**
   * Met à jour le mot de passe d'un utilisateur
   *
   * Cette méthode est requise par l'interface PasswordUpgraderInterface.
   * Elle permet de mettre à jour automatiquement les mots de passe
   * lorsque l'algorithme de hachage change.
   *
   * @param PasswordAuthenticatedUserInterface $user L'utilisateur dont le mot de passe doit être mis à jour
   * @param string $newHashedPassword Le nouveau mot de passe haché
   * @return void
   * @throws UnsupportedUserException Si l'utilisateur n'est pas une instance de User
   */
  public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void {
    if (!$user instanceof User) {
      throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
    }

    $user->setPassword($newHashedPassword);

    $this->save($user, true);
  }

  /**
   * Recherche un utilisateur par son jeton de réinitialisation de mot de passe
   *
   * Vérifie également que le jeton n'est pas expiré.
   *
   * @param string $token Le jeton de réinitialisation
   * @return User|null L'utilisateur correspondant ou null si non trouvé ou jeton expiré
   */
  public function findByResetToken(string $token): ?User {
    return $this->createQueryBuilder('u')
      ->andWhere('u.resetToken = :token')
      ->andWhere('u.resetTokenExpiresAt > :now')
      ->setParameter('token', $token)
      ->setParameter('now', new \DateTimeImmutable())
      ->getQuery()
      ->getOneOrNullResult();
  }
}