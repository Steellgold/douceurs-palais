<?php

namespace App\Service;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartItemRepository;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service de gestion du panier d'achat.
 * Gère toutes les opérations liées au panier: ajout, suppression, mise à jour
 * des articles, ainsi que la fusion de paniers lors de la connexion.
 */
class CartService {
  /**
   * Identifiant de session pour les paniers anonymes.
   */
  private ?string $sessionId = null;

  /**
   * Constructeur du service de panier.
   * Initialise l'identifiant de session pour les utilisateurs non connectés.
   *
   * @param RequestStack $requestStack Pile de requêtes pour accéder à la session
   * @param CartRepository $cartRepository Dépôt des paniers
   * @param CartItemRepository $cartItemRepository Dépôt des articles de panier
   * @param ProductRepository $productRepository Dépôt des produits
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @param Security $security Service de sécurité Symfony
   */
  public function __construct(
    private readonly RequestStack           $requestStack,
    private readonly CartRepository         $cartRepository,
    private readonly CartItemRepository     $cartItemRepository,
    private readonly ProductRepository      $productRepository,
    private readonly EntityManagerInterface $entityManager,
    private readonly Security               $security
  ) {
    $request = $this->requestStack->getCurrentRequest();

    if ($request && $request->hasSession()) {
      $session = $request->getSession();

      if (!$session->has('cart_id')) {
        $session->set('cart_id', uniqid());
      }

      $this->sessionId = $session->get('cart_id');
    } else {
      $this->sessionId = uniqid('cli_', true);
    }
  }

  /**
   * Récupère le panier actif de l'utilisateur ou crée un nouveau panier si nécessaire.
   * Utilise l'utilisateur connecté si disponible, sinon utilise l'identifiant de session.
   *
   * @return Cart Panier actif de l'utilisateur
   */
  public function getCart(): Cart {
    $user = $this->security->getUser();

    if ($user instanceof User) {
      $cart = $this->cartRepository->findActiveCartByUser($user);

      if (!$cart) {
        $cart = new Cart();
        $cart->setUser($user);
        $this->cartRepository->save($cart, true);
      }

      return $cart;
    } else {
      $cart = $this->cartRepository->findBySessionId($this->sessionId);

      if (!$cart) {
        $cart = new Cart();
        $cart->setSessionId($this->sessionId);
        $this->cartRepository->save($cart, true);
      }

      return $cart;
    }
  }

  /**
   * Vérifie si un produit peut être ajouté au panier.
   * Empêche l'ajout de produits de différentes boulangeries dans le même panier.
   *
   * @param string $productId Identifiant du produit à ajouter
   * @return bool|string Vrai si le produit peut être ajouté, message d'erreur sinon
   */
  public function canAddToCart(string $productId): bool|string {
    $cart = $this->getCart();
    $product = $this->productRepository->find($productId);

    if (!$product) {
      return "Produit non trouvé";
    }

    if ($cart->getItems()->isEmpty()) {
      return true;
    }

    $firstItem = $cart->getItems()->first();
    $cartBakery = $firstItem->getProduct()->getBakery();

    if ($product->getBakery()->getId() !== $cartBakery->getId()) {
      return "Vous ne pouvez pas ajouter des produits de différentes boulangeries dans le même panier";
    }

    return true;
  }

  /**
   * Récupère l'identifiant de la boulangerie des produits actuellement dans le panier.
   *
   * @return string|null Identifiant de la boulangerie ou null si le panier est vide
   */
  public function getCurrentCartBakeryId(): ?string {
    $cart = $this->getCart();

    if ($cart->getItems()->isEmpty()) {
      return null;
    }

    $firstItem = $cart->getItems()->first();
    return $firstItem->getProduct()->getBakery()->getId();
  }

  /**
   * Ajoute un produit au panier.
   * Si le produit est déjà dans le panier, augmente simplement la quantité.
   *
   * @param string $productId Identifiant du produit à ajouter
   * @param int $quantity Quantité à ajouter (défaut: 1)
   * @return Cart Panier mis à jour
   * @throws \InvalidArgumentException Si le produit n'existe pas ou ne peut pas être ajouté
   */
  public function addItem(string $productId, int $quantity = 1): Cart {
    $cart = $this->getCart();
    $product = $this->productRepository->find($productId);

    if (!$product) {
      throw new \InvalidArgumentException("Product not found");
    }

    $canAdd = $this->canAddToCart($productId);
    if ($canAdd !== true) {
      throw new \InvalidArgumentException($canAdd);
    }

    $item = $cart->getItemByProduct($product);

    if ($item) {
      $item->increaseQuantity($quantity);
    } else {
      $item = new CartItem();
      $item->setProduct($product);
      $item->setQuantity($quantity);
      $cart->addItem($item);
    }

    $this->cartRepository->save($cart, true);

    return $cart;
  }

  /**
   * Ajoute un produit au panier en utilisant des points de fidélité.
   *
   * @param Product $product Produit à ajouter avec des points
   * @return Cart Panier mis à jour
   * @throws \InvalidArgumentException Si le produit est déjà dans le panier normalement
   */
  public function addRedeemItem(Product $product): Cart {
    $cart = $this->getCart();

    $normalItem = $cart->getItemByProduct($product);
    if ($normalItem && !$normalItem->isRedeemedWithPoints()) {
      throw new \InvalidArgumentException("Ce produit est déjà dans votre panier. Veuillez le retirer avant d'utiliser vos points.");
    }

    $item = new CartItem();
    $item->setProduct($product);
    $item->setQuantity(1);
    $item->setRedeemedWithPoints(true);
    $cart->addItem($item);

    $this->cartRepository->save($cart, true);

    return $cart;
  }

  /**
   * Met à jour la quantité d'un article dans le panier.
   * Supprime l'article si la quantité est inférieure ou égale à zéro.
   *
   * @param string $itemId Identifiant de l'article à mettre à jour
   * @param int $quantity Nouvelle quantité
   * @return Cart Panier mis à jour
   * @throws \InvalidArgumentException Si l'article n'est pas trouvé ou ne peut pas être modifié
   */
  public function updateItemQuantity(string $itemId, int $quantity): Cart {
    $cart = $this->getCart();
    $item = $this->cartItemRepository->find($itemId);

    if (!$item || $item->getCart()->getId() !== $cart->getId()) {
      throw new \InvalidArgumentException("Item not found in cart");
    }

    if ($item->isRedeemedWithPoints()) {
      throw new \InvalidArgumentException("La quantité des produits obtenus avec des points ne peut pas être modifiée");
    }

    if ($quantity <= 0) {
      $cart->removeItem($item);
      $this->cartItemRepository->remove($item);
    } else {
      $item->setQuantity($quantity);
    }

    $this->cartRepository->save($cart, true);

    return $cart;
  }

  /**
   * Supprime un article du panier.
   * Si l'article a été obtenu avec des points, restore les points à l'utilisateur.
   *
   * @param string $itemId Identifiant de l'article à supprimer
   * @return Cart Panier mis à jour
   * @throws \InvalidArgumentException Si l'article n'est pas trouvé
   */
  public function removeItem(string $itemId): Cart {
    $cart = $this->getCart();
    $item = $this->cartItemRepository->find($itemId);

    if (!$item || $item->getCart()->getId() !== $cart->getId()) {
      throw new \InvalidArgumentException("Item not found in cart");
    }

    if ($item->isRedeemedWithPoints()) {
      $user = $this->security->getUser();
      if ($user instanceof User) {
        $product = $item->getProduct();
        $requiredPoints = $product->getRequiredPoints();
        $user->addLoyaltyPoints($requiredPoints);
        $this->entityManager->flush();
      }
    }

    $cart->removeItem($item);
    $this->cartItemRepository->remove($item);
    $this->cartRepository->save($cart, true);

    return $cart;
  }

  /**
   * Vide complètement le panier.
   *
   * @return Cart Panier vidé
   */
  public function clearCart(): Cart {
    $cart = $this->getCart();

    foreach ($cart->getItems() as $item) {
      $cart->removeItem($item);
      $this->cartItemRepository->remove($item);
    }

    $this->cartRepository->save($cart, true);

    return $cart;
  }

  /**
   * Fusionne le panier anonyme avec le panier de l'utilisateur lors de la connexion.
   *
   * @param User $user Utilisateur qui vient de se connecter
   * @return void
   */
  public function mergeAnonymousCartWithUserCart(User $user): void {
    $anonymousCart = $this->cartRepository->findBySessionId($this->sessionId);

    if (!$anonymousCart || $anonymousCart->isEmpty()) {
      return;
    }

    $userCart = $this->cartRepository->findActiveCartByUser($user);

    if (!$userCart) {
      $anonymousCart->setUser($user);
      $anonymousCart->setSessionId(null);
      $this->cartRepository->save($anonymousCart, true);
    } else {
      foreach ($anonymousCart->getItems() as $anonymousItem) {
        $product = $anonymousItem->getProduct();
        $quantity = $anonymousItem->getQuantity();

        $userItem = $userCart->getItemByProduct($product);

        if ($userItem) {
          $userItem->increaseQuantity($quantity);
        } else {
          $anonymousCart->removeItem($anonymousItem);
          $userCart->addItem($anonymousItem);
        }
      }

      $this->cartRepository->save($userCart, true);

      if ($anonymousCart->isEmpty()) {
        $this->cartRepository->remove($anonymousCart, true);
      }
    }
  }

  /**
   * Compte le nombre total d'articles dans le panier.
   *
   * @return int Nombre d'articles
   */
  public function getItemCount(): int {
    $cart = $this->getCart();
    return $cart->getTotalItems();
  }

  /**
   * Calcule le prix total du panier.
   *
   * @return float Prix total
   */
  public function getTotalPrice(): float {
    $cart = $this->getCart();
    return $cart->getTotalPrice();
  }
}