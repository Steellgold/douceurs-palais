<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Commande de création d'un utilisateur via la console
 *
 * Cette commande permet de créer rapidement un utilisateur (avec possibilité de créer un admin)
 * directement depuis la ligne de commande.
 */
#[AsCommand(
  name: 'app:create-user',
  description: 'Creates a new user',
)]
class CreateUserCommand extends Command
{
  /**
   * Constructeur de la commande
   *
   * @param EntityManagerInterface $entityManager Interface de gestion des entités Doctrine
   * @param UserPasswordHasherInterface $passwordHasher Service de hachage des mots de passe
   */
  public function __construct(
    private readonly EntityManagerInterface $entityManager,
    private readonly UserPasswordHasherInterface $passwordHasher

  ) {
    parent::__construct();
  }

  /**
   * Configure les arguments de la commande
   *
   * Définit tous les paramètres nécessaires pour créer un utilisateur:
   * - email (obligatoire)
   * - mot de passe (obligatoire)
   * - prénom (obligatoire)
   * - nom (obligatoire)
   * - statut administrateur (optionnel, par défaut: true)
   */
  protected function configure(): void
  {
    $this
      ->addArgument('email', InputArgument::REQUIRED, 'The email of the admin')
      ->addArgument('password', InputArgument::REQUIRED, 'The password of the admin')
      ->addArgument('firstName', InputArgument::REQUIRED, 'The first name of the admin')
      ->addArgument('lastName', InputArgument::REQUIRED, 'The last name of the admin')
      ->addArgument('isAdmin', InputArgument::OPTIONAL, 'Is the user an admin?', true)
    ;
  }

  /**
   * Exécute la commande pour créer un utilisateur
   *
   * Vérifie d'abord si l'utilisateur existe déjà (par email).
   * Si non, crée un nouvel utilisateur avec les données fournies.
   *
   * @param InputInterface $input Interface pour récupérer les arguments
   * @param OutputInterface $output Interface pour afficher des messages
   * @return int Code de retour (SUCCESS ou FAILURE)
   */
  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $email = $input->getArgument('email');
    $password = $input->getArgument('password');
    $firstName = $input->getArgument('firstName');
    $lastName = $input->getArgument('lastName');
    $isAdmin = $input->getArgument('isAdmin');

    // Vérifie si l'utilisateur existe déjà
    $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

    if ($existingUser) {
      $io->error(sprintf('User with email "%s" already exists', $email));
      return Command::FAILURE;
    }

    // Création du nouvel utilisateur
    $user = new User();
    $user->setEmail($email);
    $user->setFirstName($firstName);
    $user->setLastName($lastName);
    $user->setRoles(
      $isAdmin ? ['ROLE_ADMIN'] : ['ROLE_USER']
    );

    // Hachage et définition du mot de passe
    $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
    $user->setPassword($hashedPassword);

    // Persistance en base de données
    $this->entityManager->persist($user);
    $this->entityManager->flush();

    $io->success(sprintf('New' . $isAdmin ? ' admin' : '') . ' user created with email "%s"', $email);

    return Command::SUCCESS;
  }
}