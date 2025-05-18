<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Address;
use App\Form\RegistrationType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
// use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
// use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\ByteString;

/**
 * Contrôleur pour la gestion de l'authentification et des comptes utilisateurs
 *
 * Ce contrôleur gère les fonctionnalités liées à la sécurité :
 * - Connexion et déconnexion
 * - Inscription
 * - Réinitialisation de mot de passe oublié.
 */
#[Route('/auth')]
class SecurityController extends AbstractController {
  /**
   * Affiche et traite le formulaire de connexion
   *
   * Si l'utilisateur est déjà connecté, le redirige vers son espace personnel.
   *
   * @param AuthenticationUtils $authenticationUtils Utilitaire d'authentification Symfony
   * @return Response Formulaire de connexion ou redirection
   */
  #[Route('/login', name: 'app_login')]
  public function login(AuthenticationUtils $authenticationUtils): Response {

    if ($this->getUser()) {
      return $this->redirectToRoute('app_account');
    }

    // Récupération d'éventuelles erreurs de connexion
    $error = $authenticationUtils->getLastAuthenticationError();
    // Récupération du dernier nom d'utilisateur saisi
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('security/login.html.twig', [
      'last_username' => $lastUsername,
      'error' => $error,
    ]);
  }

  /**
   * Gère la déconnexion de l'utilisateur
   *
   * Cette méthode est interceptée par le pare-feu de sécurité Symfony.
   * Elle ne sera jamais exécutée directement.
   *
   * @throws \LogicException Toujours levée car la méthode ne devrait jamais être exécutée
   */
  #[Route('/logout', name: 'app_logout')]
  public function logout(): void {
    throw new \LogicException('Intercepted by the logout key on your firewall.');
  }

  /**
   * Affiche et traite le formulaire d'inscription
   *
   * Si l'utilisateur est déjà connecté, le redirige vers son espace personnel.
   *
   * @param Request $request Requête HTTP
   * @param UserPasswordHasherInterface $passwordHasher Service de hachage des mots de passe
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @param CsrfTokenManagerInterface $csrfTokenManager Gestionnaire de jetons CSRF
   * @return Response Formulaire d'inscription ou redirection
   */
  #[Route('/register', name: 'app_register')]
  public function register(
    Request $request,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $entityManager,
    CsrfTokenManagerInterface $csrfTokenManager
  ): Response {
    if ($this->getUser()) {
      return $this->redirectToRoute('app_account');
    }

    // Vérification CSRF désactivée pour le moment
    // $csrfToken = $request->request->get('_token');
    // $token = new CsrfToken('registration_form', $csrfToken);

    // if (!$csrfTokenManager->isTokenValid($token)) {
    //     throw new InvalidCsrfTokenException('Invalid CSRF token');
    // }

    $user = new User();
    $form = $this->createForm(RegistrationType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Hachage du mot de passe
      $hashedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());

      $user->setPassword($hashedPassword);

      // Traitement de l'adresse si fournie
      $address = $form->get('address')->getData();
      if ($address instanceof Address && $address->getStreet() && $address->getPostalCode() && $address->getCity()) {
        $address->setIsPrimary(true);
        if (!$address->getLabel()) {
          $address->setLabel('Main address');
        }
        $user->addAddress($address);
      }

      // Persistance de l'utilisateur en base de données
      $entityManager->persist($user);
      $entityManager->flush();

      $this->addFlash('success', 'Your account has been successfully created! You can now log in.');

      return $this->redirectToRoute('app_login');
    }

    return $this->render('security/register.html.twig', [
      'registrationForm' => $form->createView(),
    ]);
  }

  /**
   * Affiche et traite le formulaire de demande de réinitialisation de mot de passe
   *
   * Si l'utilisateur est déjà connecté, le redirige vers son espace personnel.
   *
   * @param Request $request Requête HTTP
   * @param UserRepository $userRepository Repository des utilisateurs
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection
   */
  #[Route('/forgot-password', name: 'app_forgot_password')]
  public function forgotPassword(
    Request $request,
    UserRepository $userRepository,
    EntityManagerInterface $entityManager
  ): Response {
    if ($this->getUser()) {
      return $this->redirectToRoute('app_account');
    }

    $error = null;

    if ($request->isMethod('POST')) {
      $email = $request->request->get('email');
      $user = $userRepository->findOneBy(['email' => $email]);

      if ($user) {
        // Génération d'un jeton aléatoire et définition de sa date d'expiration
        $token = ByteString::fromRandom(32)->toString();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);

        // TODO: Envoyer un email avec le lien de réinitialisation

        $entityManager->flush();
      }

      return $this->redirectToRoute('app_check_email');
    }

    return $this->render('security/forgot-password.html.twig', [
      'error' => $error
    ]);
  }

  /**
   * Affiche la page de confirmation d'envoi d'email de réinitialisation
   *
   * @return Response Page de confirmation
   */
  #[Route('/check-email', name: 'app_check_email')]
  public function checkEmail(): Response {
    return $this->render('security/check-email.html.twig');
  }

  /**
   * Affiche et traite le formulaire de réinitialisation de mot de passe
   *
   * @param string $token Jeton de réinitialisation
   * @param Request $request Requête HTTP
   * @param UserRepository $userRepository Repository des utilisateurs
   * @param UserPasswordHasherInterface $passwordHasher Service de hachage des mots de passe
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection
   */
  #[Route('/reset-password/{token}', name: 'app_reset_password')]
  public function resetPassword(
    string $token,
    Request $request,
    UserRepository $userRepository,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $entityManager
  ): Response {
    // Recherche de l'utilisateur par le jeton (valide et non expiré)
    $user = $userRepository->findByResetToken($token);

    if (!$user) {
      $this->addFlash('error', 'The reset link is invalid or has expired.');
      return $this->redirectToRoute('app_forgot_password');
    }

    $form = $this->createForm(ResetPasswordType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Suppression du jeton une fois utilisé
      $user->setResetToken(null);
      $user->setResetTokenExpiresAt(null);

      // Hachage et définition du nouveau mot de passe
      $hashedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
      $user->setPassword($hashedPassword);

      $entityManager->flush();

      $this->addFlash('success', 'Your password has been successfully reset. You can now log in.');

      return $this->redirectToRoute('app_login');
    }

    return $this->render('security/reset-password.html.twig', [
      'resetForm' => $form->createView(),
    ]);
  }
}