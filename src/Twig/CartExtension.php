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
      new TwigFunction('cart_bakery_id', [$this, 'getCurrentCartBakeryId']),
      new TwigFunction('can_add_to_cart', [$this, 'canAddToCart']),
    ];
  }

  public function getCartItemCount(): int {
    return $this->cartService->getItemCount();
  }

  public function getCartTotal(): float {
    return $this->cartService->getTotalPrice();
  }

  public function getCurrentCartBakeryId(): ?string {
    return $this->cartService->getCurrentCartBakeryId();
  }

  public function canAddToCart(string $productId): bool {
    $result = $this->cartService->canAddToCart($productId);
    return $result === true;
  }
}