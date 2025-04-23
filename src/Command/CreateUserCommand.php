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

#[AsCommand(
  name: 'app:create-user',
  description: 'Creates a new user',
)]
class CreateUserCommand extends Command
{
  public function __construct(
    private readonly EntityManagerInterface $entityManager,
    private readonly UserPasswordHasherInterface $passwordHasher

  ) {
    parent::__construct();
  }

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

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);
    $email = $input->getArgument('email');
    $password = $input->getArgument('password');
    $firstName = $input->getArgument('firstName');
    $lastName = $input->getArgument('lastName');
    $isAdmin = $input->getArgument('isAdmin');

    $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

    if ($existingUser) {
      $io->error(sprintf('User with email "%s" already exists', $email));
      return Command::FAILURE;
    }

    $user = new User();
    $user->setEmail($email);
    $user->setFirstName($firstName);
    $user->setLastName($lastName);
    $user->setRoles(
      $isAdmin ? ['ROLE_ADMIN'] : ['ROLE_USER']
    );

    $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
    $user->setPassword($hashedPassword);

    $this->entityManager->persist($user);
    $this->entityManager->flush();

    $io->success(sprintf('New' . $isAdmin ? ' admin' : '') . ' user created with email "%s"', $email);

    return Command::SUCCESS;
  }
}