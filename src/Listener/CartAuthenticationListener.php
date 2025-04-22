<?php

namespace App\Listener;

use App\Entity\User;
use App\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

readonly class CartAuthenticationListener implements EventSubscriberInterface {
  public function __construct(
    private CartService $cartService
  ) {
  }

  public static function getSubscribedEvents(): array {
    return [
      LoginSuccessEvent::class => 'onLoginSuccess',
    ];
  }

  public function onLoginSuccess(LoginSuccessEvent $event): void {
    $user = $event->getUser();

    if ($user instanceof User) {
      $this->cartService->mergeAnonymousCartWithUserCart($user);
    }
  }
}