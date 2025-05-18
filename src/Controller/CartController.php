<?php

namespace App\Controller;

use App\Service\CartService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la gestion du panier d'achat
 *
 * Ce contrôleur gère toutes les actions liées au panier :
 * - Affichage du panier
 * - Ajout, mise à jour, suppression d'articles (via API AJAX)
 * - Vidage du panier
 * - Sélection de boutique (cas de produits de plusieurs boulangeries)
 */
#[Route('/cart')]
class CartController extends AbstractController {
  /**
   * Constructeur du contrôleur de panier
   *
   * @param CartService $cartService Service de gestion du panier
   */
  public function __construct(
    private readonly CartService $cartService
  ) {
  }

  /**
   * Affiche la page du panier
   *
   * @return Response Page du panier avec tous les articles
   */
  #[Route('', name: 'app_cart_index')]
  public function index(): Response {
    $cart = $this->cartService->getCart();
    return $this->render('cart/index.html.twig', [
      'cart' => $cart,
    ]);
  }

  /**
   * API pour ajouter un article au panier
   *
   * Endpoint AJAX qui ajoute un produit au panier et retourne
   * les informations mises à jour (nombre d'articles, montant total).
   *
   * @param Request $request Requête HTTP contenant l'ID du produit et la quantité
   * @return JsonResponse Réponse JSON avec le statut de l'opération et les données du panier
   */
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

  /**
   * API pour mettre à jour la quantité d'un article
   *
   * Endpoint AJAX qui modifie la quantité d'un article existant
   * et retourne les informations mises à jour.
   *
   * @param Request $request Requête HTTP contenant l'ID de l'article et la nouvelle quantité
   * @return JsonResponse Réponse JSON avec le statut de l'opération et les données du panier
   */
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

  /**
   * API pour supprimer un article du panier
   *
   * Endpoint AJAX qui supprime un article du panier
   * et retourne les informations mises à jour.
   *
   * @param Request $request Requête HTTP contenant l'ID de l'article à supprimer
   * @return JsonResponse Réponse JSON avec le statut de l'opération et les données du panier
   */
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

  /**
   * API pour vider entièrement le panier
   *
   * Endpoint AJAX qui supprime tous les articles du panier
   * et retourne les informations mises à jour.
   *
   * @return JsonResponse Réponse JSON avec le statut de l'opération et les données du panier
   */
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

  /**
   * API pour récupérer le nombre d'articles dans le panier
   *
   * Utilisé pour mettre à jour l'interface utilisateur sans recharger la page.
   *
   * @return JsonResponse Réponse JSON contenant le nombre d'articles
   */
  #[Route('/api/count', name: 'app_cart_api_count', methods: ['GET'])]
  public function getItemCount(): JsonResponse {
    $count = $this->cartService->getItemCount();
    return $this->json([
      'count' => $count
    ]);
  }

  /**
   * Affiche la page de sélection de boutique
   *
   * Quand le panier contient des produits de plusieurs boulangeries,
   * cette page permet à l'utilisateur de choisir une seule boulangerie.
   *
   * @return Response Page de sélection ou redirection vers le checkout
   */
  #[Route('/select-shop', name: 'app_cart_select_shop')]
  public function selectShop(): Response {
    $cart = $this->cartService->getCart();

    // Redirection vers le checkout si le panier n'a pas de produits de plusieurs boutiques
    if (!$cart->hasMultipleShops()) {
      return $this->redirectToRoute('app_checkout');
    }

    return $this->render('cart/select_shop.html.twig', [
      'shops' => $cart->getUniqueShops()
    ]);
  }

  /**
   * Traite la sélection d'une boutique
   *
   * Supprime du panier tous les produits qui ne sont pas de la boulangerie sélectionnée.
   *
   * @param Request $request Requête HTTP contenant l'ID de la boulangerie sélectionnée
   * @return Response Redirection vers le checkout
   */
  #[Route('/filter-shop', name: 'app_cart_filter_shop', methods: ['POST'])]
  public function filterShop(Request $request): Response {
    $bakeryId = $request->request->get('bakery_id');

    if (!$bakeryId) {
      $this->addFlash('error', 'Veuillez sélectionner une boutique');
      return $this->redirectToRoute('app_cart_select_shop');
    }

    $cart = $this->cartService->getCart();

    // Suppression des articles qui ne sont pas de la boulangerie sélectionnée
    foreach ($cart->getItems() as $item) {
      if ($item->getProduct()->getBakery()->getId() !== $bakeryId) {
        $this->cartService->removeItem($item->getId());
      }
    }

    return $this->redirectToRoute('app_checkout');
  }
}