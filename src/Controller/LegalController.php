<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController {
  #[Route('/mentions-legales', name: 'app_mentions_legales')]
  public function legalNotices(): Response {
    return $this->render('legal/legal-notices.html.twig');
  }

  #[Route('/politique-de-protection-des-donnees', name: 'app_protection_donnees')]
  public function dataProtectionPolicy(): Response {
    return $this->render('legal/data-protection-policy.html.twig');
  }

  #[Route('/informations-de-paiement', name: 'app_informations_paiement')]
  public function paymentInformation(): Response {
    return $this->render('legal/payment-information.html.twig');
  }

  #[Route('/conditions-generales-de-vente', name: 'app_cgv')]
  public function generalTermsAndConditions(): Response {
    return $this->render('legal/general-terms-and-conditions.html.twig');
  }

  #[Route('/politique-de-cookies', name: 'app_politique_cookies')]
  public function cookiePolicy(): Response {
    return $this->render('legal/cookie-policy.html.twig');
  }
}