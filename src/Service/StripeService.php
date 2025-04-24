<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly class StripeService {
  public function __construct(
    private ParameterBagInterface $parameterBag,
    private UrlGeneratorInterface $urlGenerator
  ) {
    Stripe::setApiKey($this->parameterBag->get('stripe_secret_key'));
  }

  /**
   * @param Order $order
   * @return Session
   * @throws ApiErrorException
   */
  public function createCheckoutSession(Order $order): Session {
    $lineItems = [];

    foreach ($order->getItems() as $item) {
      $product = $item->getProduct();
      $bakeryName = $product->getBakery()->getName();

      $lineItems[] = [
        'price_data' => [
          'currency' => 'eur',
          'unit_amount' => (int)($item->getPrice() * 100),
          'product_data' => [
            'name' => $product->getName(),
            'description' => "De " . $bakeryName . " - " . ($product->getDescription() ?? ''),
            'images' => $product->getImages() ? [$product->getMainImage()] : [],
          ],
        ],
        'quantity' => $item->getQuantity(),
      ];
    }

    $checkoutSession = Session::create([
      'payment_method_types' => ['card'],
      'line_items' => $lineItems,
      'mode' => 'payment',
      'customer_email' => $order->getUser()->getEmail(),
      'success_url' => $this->urlGenerator->generate('app_checkout_success', [
        'session_id' => '{CHECKOUT_SESSION_ID}',
      ], UrlGeneratorInterface::ABSOLUTE_URL),
      'cancel_url' => $this->urlGenerator->generate('app_checkout_cancel', [
        'order_id' => $order->getId(),
      ], UrlGeneratorInterface::ABSOLUTE_URL),
      'metadata' => [
        'order_id' => $order->getId(),
      ],
    ]);

    $order->setStripeSessionId($checkoutSession->id);
    $order->setStatus(Order::STATUS_PAYMENT_PROCESSING);
    $order->setStripePaymentIntentId($checkoutSession->payment_intent);

    return $checkoutSession;
  }

  /**
   * @param Order $order
   * @return bool
   * @throws ApiErrorException
   */
  public function verifyPayment(Order $order): bool {
    if (!$order->getStripeSessionId()) {
      return false;
    }

    $session = Session::retrieve($order->getStripeSessionId());

    if ($session->payment_status === 'paid') {
      $order->setStatus(Order::STATUS_PAID);
      return true;
    }

    return false;
  }

  /**
   * @return string The public key
   */
  public function getPublicKey(): string {
    return $this->parameterBag->get('stripe_public_key');
  }
}