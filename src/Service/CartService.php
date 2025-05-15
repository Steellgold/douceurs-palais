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

class CartService {
  private ?string $sessionId = null;

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

  public function getCurrentCartBakeryId(): ?string {
    $cart = $this->getCart();

    if ($cart->getItems()->isEmpty()) {
      return null;
    }

    $firstItem = $cart->getItems()->first();
    return $firstItem->getProduct()->getBakery()->getId();
  }

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

  public function clearCart(): Cart {
    $cart = $this->getCart();

    foreach ($cart->getItems() as $item) {
      $cart->removeItem($item);
      $this->cartItemRepository->remove($item);
    }

    $this->cartRepository->save($cart, true);

    return $cart;
  }

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

  public function getItemCount(): int {
    $cart = $this->getCart();
    return $cart->getTotalItems();
  }

  public function getTotalPrice(): float {
    $cart = $this->getCart();
    return $cart->getTotalPrice();
  }
}