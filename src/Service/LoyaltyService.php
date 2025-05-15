<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Order;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

readonly class LoyaltyService {
  public function __construct(
    private readonly EntityManagerInterface $entityManager,
    private readonly ProductRepository      $productRepository,
    private readonly CartService            $cartService
  ) {
  }

  public function addPointsFromOrder(Order $order): void {
    $user = $order->getUser();
    if ($user) {
      $pointsToAdd = (int)$order->getTotalAmount();
      $user->addLoyaltyPoints($pointsToAdd);
      $this->entityManager->flush();
    }
  }

  public function redeemProductWithPoints(User $user, Product $product): bool {
    if (!$product->isAvailableWithPoints()) {
      return false;
    }

    $requiredPoints = $product->getRequiredPoints();

    if ($user->getLoyaltyPoints() < $requiredPoints) {
      return false;
    }

    try {
      $user->spendLoyaltyPoints($requiredPoints);
      $this->entityManager->flush();

      $this->cartService->addRedeemItem($product);

      return true;
    } catch (\Exception $e) {
      return false;
    }
  }
}