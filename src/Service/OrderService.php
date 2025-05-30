<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Cart;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Service de gestion des commandes.
 * Gère la création, le suivi et la finalisation des commandes,
 * ainsi que l'envoi des emails de confirmation.
 */
class OrderService {

  /**
   * Constructeur du service de commande.
   *
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @param OrderRepository $orderRepository Dépôt des commandes
   * @param OrderItemRepository $orderItemRepository Dépôt des articles de commande
   * @param CartService $cartService Service de gestion des paniers
   * @param MailerInterface $mailer Service d'envoi d'emails Symfony
   * @param EmailService $emailService Service personnalisé d'envoi d'emails
   */
  public function __construct(
    private EntityManagerInterface $entityManager,
    private OrderRepository        $orderRepository,
    private OrderItemRepository    $orderItemRepository,
    private readonly CartService   $cartService,
    private MailerInterface        $mailer,
    private EmailService  $emailService,
  ) {
  }

  /**
   * Crée une nouvelle commande à partir d'un panier.
   * Transfère tous les articles du panier vers la commande et calcule le montant total.
   *
   * @param Cart $cart Panier source pour la commande
   * @param Address $shippingAddress Adresse de livraison
   * @param Address|null $billingAddress Adresse de facturation (utilise l'adresse de livraison si non fournie)
   * @return Order Commande nouvellement créée
   */
  public function createOrderFromCart(Cart $cart, Address $shippingAddress, ?Address $billingAddress = null): Order
  {
    $order = new Order();
    $order->setUser($cart->getUser());
    $totalAmount = 0;
    $totalTaxAmount = 0;
    $totalSubtotalAmount = 0;

    $order->setShippingAddress($this->addressToArray($shippingAddress));

    if ($billingAddress) $order->setBillingAddress($this->addressToArray($billingAddress));
    else $order->setBillingAddress($this->addressToArray($shippingAddress));

    foreach ($cart->getItems() as $cartItem) {
      $orderItem = new OrderItem();
      $orderItem->setProduct($cartItem->getProduct());
      $orderItem->setQuantity($cartItem->getQuantity());

      if ($cartItem->isRedeemedWithPoints()) {
        $orderItem->setPrice(0);
        $orderItem->setRedeemedWithPoints(true);
      } else {
        $orderItem->setPrice($cartItem->getProduct()->getPrice());
        $itemTotal = $orderItem->getPrice() * $orderItem->getQuantity();
        $totalAmount += $itemTotal;

        // Calcul de la TVA pour cet article
        $product = $cartItem->getProduct();
        $itemSubtotal = $product->getPriceExcludingTax() * $orderItem->getQuantity();
        $itemTax = $itemTotal - $itemSubtotal;

        $totalSubtotalAmount += $itemSubtotal;
        $totalTaxAmount += $itemTax;
      }

      $order->addItem($orderItem);
    }

    $order->setTotalAmount($totalAmount);
    $order->setSubtotalAmount($totalSubtotalAmount);
    $order->setTaxAmount($totalTaxAmount);

    // Déterminer le taux de TVA moyen (ou utiliser 20% par défaut)
    if ($totalSubtotalAmount > 0) {
      $averageTaxRate = ($totalTaxAmount / $totalSubtotalAmount) * 100;
      $order->setTaxRate($averageTaxRate);
    }

    $this->entityManager->persist($order);
    $this->entityManager->flush();

    return $order;
  }

  /**
   * Convertit une entité Address en tableau associatif pour stockage dans la commande.
   *
   * @param Address $address Adresse à convertir
   * @return array Tableau associatif des propriétés de l'adresse
   */
  private function addressToArray(Address $address): array {
    return [
      'label' => $address->getLabel(),
      'street' => $address->getStreet(),
      'complement' => $address->getComplement(),
      'postalCode' => $address->getPostalCode(),
      'city' => $address->getCity(),
      'fullAddress' => $address->getFullAddress(),
    ];
  }

  /**
   * Récupère toutes les commandes d'un utilisateur.
   *
   * @param User $user Utilisateur dont on veut récupérer les commandes
   * @return array Liste des commandes de l'utilisateur
   */
  public function getOrdersByUser(User $user): array {
    return $this->orderRepository->findByUser($user);
  }

  /**
   * Récupère une commande par son identifiant.
   *
   * @param string $id Identifiant de la commande
   * @return Order|null Commande trouvée ou null si non trouvée
   */
  public function getOrderById(string $id): ?Order {
    return $this->orderRepository->find($id);
  }

  /**
   * Récupère une commande par l'identifiant de session Stripe.
   *
   * @param string $sessionId Identifiant de la session Stripe
   * @return Order|null Commande trouvée ou null si non trouvée
   */
  public function getOrderByStripeSessionId(string $sessionId): ?Order {
    return $this->orderRepository->findByStripeSessionId($sessionId);
  }

  /**
   * Finalise une commande après paiement réussi.
   * Met à jour le statut, attribue des points de fidélité et envoie la confirmation.
   *
   * @param Order $order Commande à finaliser
   * @return void
   */
  public function completeOrder(Order $order): void {
    $order->setStatus(Order::STATUS_PAID);
    $this->entityManager->flush();

    $user = $order->getUser();
    if ($user) {
      $pointsToAdd = (int)$order->getTotalAmount();
      $user->addLoyaltyPoints($pointsToAdd);
    }

    $this->entityManager->flush();
    $this->sendOrderConfirmationEmail($order);

    if ($user) {
      $cart = $this->cartService->getCart();
      if ($cart->getUser() && $cart->getUser()->getId() === $user->getId()) {
        $cart->clear();
        $this->entityManager->persist($cart);
        $this->entityManager->flush();
      }
    }
  }

  /**
   * Envoie un email de confirmation de commande à l'utilisateur.
   *
   * @param Order $order Commande confirmée
   * @return void
   */
  private function sendOrderConfirmationEmail(Order $order): void {
    $user = $order->getUser();

    if (!$user) {
      return;
    }

    $this->emailService->sendTemplate(
      $user->getEmail(),
      'Confirmation de votre commande #' . $order->getReference(),
      'emails/order_confirmation.html.twig',
      ['order' => $order]
    );
  }

  /**
   * Annule une commande.
   * Met à jour le statut de la commande à "annulée".
   *
   * @param Order $order Commande à annuler
   * @return void
   */
  public function cancelOrder(Order $order): void {
    $order->setStatus(Order::STATUS_CANCELLED);
    $this->entityManager->flush();
  }
}