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
 * Commande Symfony permettant de créer un nouvel utilisateur via la console.
 * Cette commande est utile pour créer des utilisateurs administrateurs
 * ou des comptes de test rapidement sans passer par l'interface web.
 */
#[AsCommand(
  name: 'app:create-user',
  description: 'Creates a new user',
)]
class CreateUserCommand extends Command {
  /**
   * Constructeur de la commande CreateUserCommand.
   *
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine pour persister l'utilisateur
   * @param UserPasswordHasherInterface $passwordHasher Service de hachage de mot de passe
   */
  public function __construct(
    private readonly EntityManagerInterface      $entityManager,
    private readonly UserPasswordHasherInterface $passwordHasher

  ) {
    parent::__construct();
  }

  /**
   * Configure la commande en définissant les arguments attendus.
   *
   * @return void
   */
  protected function configure(): void {
    $this
      ->addArgument('email', InputArgument::REQUIRED, 'The email of the admin')
      ->addArgument('password', InputArgument::REQUIRED, 'The password of the admin')
      ->addArgument('firstName', InputArgument::REQUIRED, 'The first name of the admin')
      ->addArgument('lastName', InputArgument::REQUIRED, 'The last name of the admin')
      ->addArgument('isAdmin', InputArgument::OPTIONAL, 'Is the user an admin?', false);
  }

  /**
   * Exécute la commande de création d'utilisateur.
   * Vérifie d'abord si l'utilisateur existe déjà, puis crée un nouvel utilisateur
   * avec les informations fournies si l'email n'est pas déjà utilisé.
   *
   * @param InputInterface $input Interface d'entrée pour récupérer les arguments
   * @param OutputInterface $output Interface de sortie pour afficher les messages
   * @return int Code de retour de la commande (SUCCESS ou FAILURE)
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
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

    // Persistance de l'utilisateur en base de données
    $this->entityManager->persist($user);
    $this->entityManager->flush();

    $io->success(sprintf('New' . $isAdmin ? ' admin' : '') . ' user created with email "%s"', $email);

    return Command::SUCCESS;
  }
}