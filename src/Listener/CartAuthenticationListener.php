<?php

namespace App\Listener;

use App\Entity\User;
use App\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Écouteur d'événements d'authentification pour gérer les paniers.
 * Permet de fusionner le panier anonyme avec le panier de l'utilisateur
 * lorsqu'il se connecte, pour éviter la perte des articles.
 */
readonly class CartAuthenticationListener implements EventSubscriberInterface {
  /**
   * Constructeur de l'écouteur d'authentification.
   *
   * @param CartService $cartService Service de gestion des paniers
   */
  public function __construct(
    private CartService $cartService
  ) {
  }

  /**
   * Définit les événements auxquels cet écouteur doit réagir.
   * Dans ce cas, uniquement l'événement de connexion réussie.
   *
   * @return array Tableau associatif des événements et méthodes à appeler
   */
  public static function getSubscribedEvents(): array {
    return [
      LoginSuccessEvent::class => 'onLoginSuccess',
    ];
  }

  /**
   * Méthode appelée lors d'une connexion réussie.
   * Fusionne le panier anonyme (basé sur la session) avec le panier
   * associé à l'utilisateur qui vient de se connecter.
   *
   * @param LoginSuccessEvent $event Événement de connexion réussie
   * @return void
   */
  public function onLoginSuccess(LoginSuccessEvent $event): void {
    $user = $event->getUser();

    if ($user instanceof User) {
      $this->cartService->mergeAnonymousCartWithUserCart($user);
    }
  }
}