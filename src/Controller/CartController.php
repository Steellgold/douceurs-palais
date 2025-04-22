<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cart')]
class CartController extends AbstractController {
  public function __construct(
    private readonly CartService $cartService
  ) {
  }

  #[Route('', name: 'app_cart_index')]
  public function index(): Response {
    $cart = $this->cartService->getCart();
    return $this->render('cart/index.html.twig', [
      'cart' => $cart,
    ]);
  }

  #[Route('/api/add', name: 'app_cart_api_add', methods: ['POST'])]
  public function addToCart(Request $request): JsonResponse {
    $productId = $request->request->get('productId');
    $quantity = (int)$request->request->get('quantity', 1);

    // Debug logging
    $this->logDebug('Adding product to cart', [
      'productId' => $productId,
      'quantity' => $quantity,
    ]);

    try {
      $cart = $this->cartService->addItem($productId, $quantity);

      $this->logDebug('Product added to cart', [
        'itemCount' => $cart->getTotalItems(),
        'total' => $cart->getTotalPrice(),
      ]);

      return $this->json([
        'success' => true,
        'message' => 'Produit ajouté au panier',
        'itemCount' => $cart->getTotalItems(),
        'total' => $cart->getTotalPrice(),
      ]);
    } catch (\Exception $e) {
      $this->logDebug('Error adding product to cart', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
      ]);

      return $this->json([
        'success' => false,
        'message' => $e->getMessage()
      ], Response::HTTP_BAD_REQUEST);
    }
  }

  #[Route('/api/update', name: 'app_cart_api_update', methods: ['POST'])]
  public function updateCartItem(Request $request): JsonResponse {
    $itemId = $request->request->get('itemId');
    $quantity = (int)$request->request->get('quantity', 1);

    try {
      $cart = $this->cartService->updateItemQuantity($itemId, $quantity);
      return $this->json([
        'success' => true,
        'message' => 'Quantité mise à jour',
        'itemCount' => $cart->getTotalItems(),
        'total' => $cart->getTotalPrice(),
      ]);
    } catch (\Exception $e) {
      return $this->json([
        'success' => false,
        'message' => $e->getMessage()
      ], Response::HTTP_BAD_REQUEST);
    }
  }

  #[Route('/api/remove', name: 'app_cart_api_remove', methods: ['POST'])]
  public function removeCartItem(Request $request): JsonResponse {
    $itemId = $request->request->get('itemId');

    try {
      $cart = $this->cartService->removeItem($itemId);
      return $this->json([
        'success' => true,
        'message' => 'Produit supprimé du panier',
        'itemCount' => $cart->getTotalItems(),
        'total' => $cart->getTotalPrice(),
      ]);
    } catch (\Exception $e) {
      return $this->json([
        'success' => false,
        'message' => $e->getMessage()
      ], Response::HTTP_BAD_REQUEST);
    }
  }

  #[Route('/api/clear', name: 'app_cart_api_clear', methods: ['POST'])]
  public function clearCart(): JsonResponse {
    $cart = $this->cartService->clearCart();
    return $this->json([
      'success' => true,
      'message' => 'Panier vidé',
      'itemCount' => $cart->getTotalItems(),
      'total' => $cart->getTotalPrice(),
    ]);
  }

  #[Route('/api/count', name: 'app_cart_api_count', methods: ['GET'])]
  public function getItemCount(): JsonResponse {
    $count = $this->cartService->getItemCount();
    return $this->json([
      'count' => $count
    ]);
  }

  /**
   * Helper method for debug logging
   */
  private function logDebug(string $message, array $context = []): void {
    // Log to file
    $logDir = $this->getParameter('kernel.logs_dir');
    $logFile = $logDir . '/cart.log';

    $logMessage = '[' . date('Y-m-d H:i:s') . '] ' . $message . ': ' . json_encode($context, JSON_PRETTY_PRINT) . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
  }
}