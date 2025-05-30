<?php

namespace App\Twig;

use App\Entity\Order;
use App\Service\CartService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TaxExtension extends AbstractExtension {
  private CartService $cartService;

  public function __construct(CartService $cartService) {
    $this->cartService = $cartService;
  }

  public function getFunctions(): array {
    return [
      new TwigFunction('cart_subtotal_excluding_tax', [$this, 'getCartSubtotalExcludingTax']),
      new TwigFunction('cart_tax_amount', [$this, 'getCartTaxAmount']),
      new TwigFunction('product_price_excluding_tax', [$this, 'getProductPriceExcludingTax']),
      new TwigFunction('order_tax_breakdown', [$this, 'getOrderTaxBreakdown']),
    ];
  }

  public function getCartSubtotalExcludingTax(): float {
    $cart = $this->cartService->getCart();
    $subtotal = 0;

    foreach ($cart->getItems() as $item) {
      if (!$item->isRedeemedWithPoints()) {
        $subtotal += $item->getProduct()->getPriceExcludingTax() * $item->getQuantity();
      }
    }

    return $subtotal;
  }

  public function getCartTaxAmount(): float {
    $cart = $this->cartService->getCart();
    $taxAmount = 0;

    foreach ($cart->getItems() as $item) {
      if (!$item->isRedeemedWithPoints()) {
        $taxAmount += $item->getProduct()->getTaxAmount() * $item->getQuantity();
      }
    }

    return $taxAmount;
  }

  public function getProductPriceExcludingTax($product): float {
    return $product->getPriceExcludingTax();
  }

  public function getOrderTaxBreakdown(Order $order): array {
    $breakdown = [];

    foreach ($order->getItems() as $item) {
      if (!$item->isRedeemedWithPoints()) {
        $taxRate = $item->getProduct()->getTaxRate();

        if (!isset($breakdown[$taxRate])) {
          $breakdown[$taxRate] = [
            'rate' => $taxRate,
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0
          ];
        }

        $itemSubtotal = $item->getProduct()->getPriceExcludingTax() * $item->getQuantity();
        $itemTax = $item->getProduct()->getTaxAmount() * $item->getQuantity();
        $itemTotal = $item->getPrice() * $item->getQuantity();

        $breakdown[$taxRate]['subtotal'] += $itemSubtotal;
        $breakdown[$taxRate]['tax_amount'] += $itemTax;
        $breakdown[$taxRate]['total'] += $itemTotal;
      }
    }

    return $breakdown;
  }
}