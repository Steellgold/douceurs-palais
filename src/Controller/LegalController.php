<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour les pages d'informations légales
 *
 * Ce contrôleur gère l'affichage des différentes pages d'informations légales
 * telles que les mentions légales, politique de cookies, CGV, etc.
 */
class LegalController extends AbstractController {
  /**
   * Affiche la page des mentions légales
   *
   * @return Response Page des mentions légales
   */
  #[Route('/mentions-legales', name: 'app_mentions_legales')]
  public function legalNotices(): Response {
    return $this->render('legal/legal-notices.html.twig');
  }

  /**
   * Affiche la page de politique de protection des données
   *
   * @return Response Page de politique de protection des données
   */
  #[Route('/politique-de-protection-des-donnees', name: 'app_protection_donnees')]
  public function dataProtectionPolicy(): Response {
    return $this->render('legal/data-protection-policy.html.twig');
  }

  /**
   * Affiche la page d'informations sur les paiements
   *
   * @return Response Page d'informations sur les paiements
   */
  #[Route('/informations-de-paiement', name: 'app_informations_paiement')]
  public function paymentInformation(): Response {
    return $this->render('legal/payment-information.html.twig');
  }

  /**
   * Affiche la page des conditions générales de vente
   *
   * @return Response Page des conditions générales de vente
   */
  #[Route('/conditions-generales-de-vente', name: 'app_cgv')]
  public function generalTermsAndConditions(): Response {
    return $this->render('legal/general-terms-and-conditions.html.twig');
  }

  /**
   * Affiche la page de politique des cookies
   *
   * @return Response Page de politique des cookies
   */
  #[Route('/politique-de-cookies', name: 'app_politique_cookies')]
  public function cookiePolicy(): Response {
    return $this->render('legal/cookie-policy.html.twig');
  }
}