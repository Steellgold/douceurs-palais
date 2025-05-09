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

class OrderService {

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
   * @param Cart $cart
   * @param Address $shippingAddress
   * @param Address|null $billingAddress
   * @return Order
   */
  public function createOrderFromCart(Cart $cart, Address $shippingAddress, ?Address $billingAddress = null): Order {
    $order = new Order();
    $order->setUser($cart->getUser());
    $order->setTotalAmount($cart->getTotalPrice());

    $order->setShippingAddress($this->addressToArray($shippingAddress));

    if ($billingAddress) $order->setBillingAddress($this->addressToArray($billingAddress));
    else $order->setBillingAddress($this->addressToArray($shippingAddress));

    foreach ($cart->getItems() as $cartItem) {
      $orderItem = new OrderItem();
      $orderItem->setProduct($cartItem->getProduct());
      $orderItem->setQuantity($cartItem->getQuantity());
      $orderItem->setPrice($cartItem->getProduct()->getPrice());
      $order->addItem($orderItem);
    }

    $this->entityManager->persist($order);
    $this->entityManager->flush();

    return $order;
  }

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

  public function getOrdersByUser(User $user): array {
    return $this->orderRepository->findByUser($user);
  }

  public function getOrderById(string $id): ?Order {
    return $this->orderRepository->find($id);
  }

  public function getOrderByStripeSessionId(string $sessionId): ?Order {
    return $this->orderRepository->findByStripeSessionId($sessionId);
  }

  public function completeOrder(Order $order): void {
    $order->setStatus(Order::STATUS_PAID);
    $this->entityManager->flush();

    $user = $order->getUser();

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

  public function cancelOrder(Order $order): void {
    $order->setStatus(Order::STATUS_CANCELLED);
    $this->entityManager->flush();
  }
}