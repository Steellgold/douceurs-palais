<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Order;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion du système de fidélité.
 * Gère l'attribution et l'utilisation des points de fidélité
 * pour les utilisateurs.
 */
readonly class LoyaltyService {
  /**
   * Constructeur du service de fidélité.
   *
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @param ProductRepository $productRepository Dépôt des produits
   * @param CartService $cartService Service de gestion des paniers
   */
  public function __construct(
    private readonly EntityManagerInterface $entityManager,
    private readonly ProductRepository      $productRepository,
    private readonly CartService            $cartService
  ) {
  }

  /**
   * Ajoute des points de fidélité à un utilisateur après une commande.
   * Le nombre de points ajoutés correspond au montant total de la commande.
   *
   * @param Order $order Commande finalisée
   * @return void
   */
  public function addPointsFromOrder(Order $order): void {
    $user = $order->getUser();
    if ($user) {
      $pointsToAdd = (int)$order->getTotalAmount();
      $user->addLoyaltyPoints($pointsToAdd);
      $this->entityManager->flush();
    }
  }

  /**
   * Permet à un utilisateur d'échanger ses points de fidélité contre un produit.
   * Vérifie que le produit est disponible avec des points et que l'utilisateur
   * dispose de suffisamment de points.
   *
   * @param User $user Utilisateur qui échange ses points
   * @param Product $product Produit à obtenir avec des points
   * @return bool Succès ou échec de l'opération
   */
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