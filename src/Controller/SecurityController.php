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
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\ByteString;

class SecurityController extends AbstractController {
  #[Route('/login', name: 'app_login')]
  public function login(AuthenticationUtils $authenticationUtils): Response {
    if ($this->getUser()) {
      return $this->redirectToRoute('app_account');
    }

    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('security/login.html.twig', [
      'last_username' => $lastUsername,
      'error' => $error,
    ]);
  }

  #[Route('/logout', name: 'app_logout')]
  public function logout(): void {
    throw new \LogicException('Intercepted by the logout key on your firewall.');
  }

  #[Route('/register', name: 'app_register')]
  public function register(
    Request $request,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $entityManager
  ): Response {
    if ($this->getUser()) {
      return $this->redirectToRoute('app_account');
    }

    $user = new User();
    $form = $this->createForm(RegistrationType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $hashedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());

      $user->setPassword($hashedPassword);

      $address = $form->get('address')->getData();
      if ($address instanceof Address && $address->getStreet() && $address->getPostalCode() && $address->getCity()) {
        $address->setIsPrimary(true);
        if (!$address->getLabel()) {
          $address->setLabel('Main address');
        }
        $user->addAddress($address);
      }

      $entityManager->persist($user);
      $entityManager->flush();

      $this->addFlash('success', 'Your account has been successfully created! You can now log in.');

      return $this->redirectToRoute('app_login');
    }

    return $this->render('security/register.html.twig', [
      'registrationForm' => $form->createView(),
    ]);
  }

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
        $token = ByteString::fromRandom(32)->toString();
        $expiresAt = new \DateTimeImmutable('+1 hour');

        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);

        // SEND A EMAIL

        $entityManager->flush();
      }

      return $this->redirectToRoute('app_check_email');
    }

    return $this->render('security/forgot_password.html.twig', [
      'error' => $error
    ]);
  }

  #[Route('/check-email', name: 'app_check_email')]
  public function checkEmail(): Response {
    return $this->render('security/check_email.html.twig');
  }

  #[Route('/reset-password/{token}', name: 'app_reset_password')]
  public function resetPassword(
    string $token,
    Request $request,
    UserRepository $userRepository,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface $entityManager
  ): Response {
    $user = $userRepository->findByResetToken($token);

    if (!$user) {
      $this->addFlash('error', 'The reset link is invalid or has expired.');
      return $this->redirectToRoute('app_forgot_password');
    }

    $form = $this->createForm(ResetPasswordType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $user->setResetToken(null);
      $user->setResetTokenExpiresAt(null);

      $hashedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
      $user->setPassword($hashedPassword);

      $entityManager->flush();

      $this->addFlash('success', 'Your password has been successfully reset. You can now log in.');

      return $this->redirectToRoute('app_login');
    }

    return $this->render('security/reset_password.html.twig', [
      'resetForm' => $form->createView(),
    ]);
  }
}