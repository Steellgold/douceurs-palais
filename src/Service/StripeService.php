<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Service d'intégration avec l'API Stripe.
 * Gère l'interaction avec Stripe pour le traitement des paiements,
 * y compris la création de sessions de paiement et la vérification des paiements.
 */
readonly class StripeService {
  /**
   * Constructeur du service Stripe.
   * Initialise l'API Stripe avec la clé secrète.
   *
   * @param ParameterBagInterface $parameterBag Gestionnaire de paramètres pour accéder aux clés API
   * @param UrlGeneratorInterface $urlGenerator Générateur d'URL pour les redirections
   */
  public function __construct(
    private ParameterBagInterface $parameterBag,
    private UrlGeneratorInterface $urlGenerator
  ) {
    Stripe::setApiKey($this->parameterBag->get('stripe_secret_key'));
  }

  /**
   * Crée une session de paiement Stripe pour une commande.
   * Configure les articles à payer, les URLs de redirection et les métadonnées.
   *
   * @param Order $order Commande pour laquelle créer une session de paiement
   * @return Session Session de paiement Stripe créée
   * @throws ApiErrorException En cas d'erreur avec l'API Stripe
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
      'success_url' => $this->urlGenerator->generate('app_checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
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
   * Vérifie si un paiement a été effectué avec succès.
   * Interroge Stripe pour connaître l'état du paiement.
   *
   * @param Order $order Commande à vérifier
   * @return bool Vrai si le paiement a été effectué, faux sinon
   * @throws ApiErrorException En cas d'erreur avec l'API Stripe
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
   * Récupère la clé publique Stripe à utiliser côté client.
   *
   * @return string Clé publique Stripe
   */
  public function getPublicKey(): string {
    return $this->parameterBag->get('stripe_public_key');
  }
}