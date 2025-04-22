<?php

namespace App\Twig;

use App\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension {
  private CartService $cartService;

  public function __construct(CartService $cartService) {
    $this->cartService = $cartService;
  }

  public function getFunctions(): array {
    return [
      new TwigFunction('cart_item_count', [$this, 'getCartItemCount']),
      new TwigFunction('cart_total', [$this, 'getCartTotal']),
    ];
  }

  public function getCartItemCount(): int {
    return $this->cartService->getItemCount();
  }

  public function getCartTotal(): float {
    return $this->cartService->getTotalPrice();
  }
}