<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AccountPageController extends AbstractController {
  #[Route('/login', name: 'app_login_page')]
  public function login(): Response {
    return $this->render('account_page/login.html.twig', []);
  }

  #[Route('/register', name: 'app_register_page')]
  public function register(): Response {
    return $this->render('account_page/register.html.twig', []);
  }

  #[Route('/forgot-password', name: 'app_forgot_password_page')]
  public function resetPassword(): Response {
    return $this->render('account_page/forgot-password.html.twig', []);
  }

  #[Route('/reset-password/{token}', name: 'app_reset_password_token_page')]
  public function resetPasswordToken(string $token): Response {
    return $this->render('account_page/reset-password.html.twig', [
      'token' => $token,
    ]);
  }

  #[Route('/check-email', name: 'app_check_email_page')]
  public function checkEmail(): Response {
    return $this->render('account_page/check-email.html.twig', []);
  }
}
