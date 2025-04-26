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

    try {
      $cart = $this->cartService->addItem($productId, $quantity);

      return $this->json([
        'success' => true,
        'message' => 'Produit ajouté au panier',
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

  #[Route('/select-shop', name: 'app_cart_select_shop')]
  public function selectShop(): Response {
    $cart = $this->cartService->getCart();

    if (!$cart->hasMultipleShops()) {
      return $this->redirectToRoute('app_checkout');
    }

    return $this->render('cart/select_shop.html.twig', [
      'shops' => $cart->getUniqueShops()
    ]);
  }

  #[Route('/filter-shop', name: 'app_cart_filter_shop', methods: ['POST'])]
  public function filterShop(Request $request): Response {
    $bakeryId = $request->request->get('bakery_id');

    if (!$bakeryId) {
      $this->addFlash('error', 'Veuillez sélectionner une boutique');
      return $this->redirectToRoute('app_cart_select_shop');
    }

    $cart = $this->cartService->getCart();

    foreach ($cart->getItems() as $item) {
      if ($item->getProduct()->getBakery()->getId() !== $bakeryId) {
        $this->cartService->removeItem($item->getId());
      }
    }

    return $this->redirectToRoute('app_checkout');
  }
}