<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;

class StripeWebhookController extends AbstractController {
  public function __construct(
    private readonly OrderRepository        $orderRepository,
    private readonly OrderService           $orderService,
    private readonly EntityManagerInterface $entityManager,
    private readonly ParameterBagInterface  $parameterBag,
    private readonly LoggerInterface        $logger
  ) {
  }

  #[Route('/webhook/stripe', name: 'app_webhook_stripe', methods: ['POST'])]
  public function index(Request $request): Response {
    $signature = $request->headers->get('Stripe-Signature');
    $payload = $request->getContent();
    $webhookSecret = $this->parameterBag->get('stripe_webhook_secret');

    try {
      $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
      $this->logger->info('Webhook received: ' . $event->type);

      if (in_array($event->type, ['checkout.session.completed', 'payment_intent.succeeded', 'payment_intent.payment_failed'])) {
        $this->logger->info('Event data: ' . json_encode($event->data->object));
      }

      switch ($event->type) {
        case 'checkout.session.completed':
          $this->handleCheckoutSessionCompleted($event);
          break;
        case 'payment_intent.succeeded':
          $this->handlePaymentIntentSucceeded($event);
          break;
        case 'payment_intent.payment_failed':
          $this->handlePaymentIntentFailed($event);
          break;
        default:
          $this->logger->info('Unhandled event type: ' . $event->type);
      }

      return new Response('Webhook received', Response::HTTP_OK);
    } catch (SignatureVerificationException $e) {
      $this->logger->error('Webhook signature verification failed: ' . $e->getMessage());
      return new Response('Webhook signature verification failed', Response::HTTP_BAD_REQUEST);
    } catch (\Exception $e) {
      $this->logger->error('Webhook error: ' . $e->getMessage());
      return new Response('Webhook error: ' . $e->getMessage(), Response::HTTP_BAD_REQUEST);
    }
  }

  private function handleCheckoutSessionCompleted(Event $event): void {
    $session = $event->data->object;
    $orderId = $session->metadata->order_id ?? null;

    if (!$orderId) {
      $this->logger->error('No order ID found in session metadata');
      return;
    }

    $order = $this->orderRepository->find($orderId);

    if (!$order) {
      $this->logger->error('Order not found: ' . $orderId);
      return;
    }

    if (!$order->getStripePaymentIntentId() && isset($session->payment_intent)) {
      $this->logger->info('Updating payment intent ID: ' . $session->payment_intent);
      $order->setStripePaymentIntentId($session->payment_intent);
      $this->entityManager->flush();
    }

    if ($session->payment_status === 'paid') {
      $this->orderService->completeOrder($order);
      $this->logger->info('Order completed: ' . $order->getReference());
    } else {
      $this->logger->info('Payment pending for order: ' . $order->getReference());
    }
  }

  private function handlePaymentIntentSucceeded(Event $event): void {
    $paymentIntent = $event->data->object;
    $paymentIntentId = $paymentIntent->id;

    $order = $this->orderRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);

    if (!$order && str_starts_with($paymentIntentId, 'pi_')) {
      $this->logger->info('Trying to find order with payment intent ID without prefix: ' . $paymentIntentId);
      $order = $this->orderRepository->findOneBy(['stripePaymentIntentId' => substr($paymentIntentId, 3)]);
    }

    if (!$order) {
      $this->logger->info('Payment intent ID not found in database, attempting to find similar: ' . $paymentIntentId);
      $order = $this->orderRepository->findByPartialPaymentIntentId($paymentIntentId);
    }

    if (!$order) {
      $this->logger->error('Order not found for payment intent: ' . $paymentIntentId);
      return;
    }

    if ($order->getStripePaymentIntentId() !== $paymentIntentId) {
      $this->logger->info('Updating payment intent ID from ' . $order->getStripePaymentIntentId() . ' to ' . $paymentIntentId);
      $order->setStripePaymentIntentId($paymentIntentId);
      $this->entityManager->flush();
    }

    $this->orderService->completeOrder($order);
    $this->logger->info('Order completed via payment intent: ' . $order->getReference());
  }

  private function handlePaymentIntentFailed(Event $event): void {
    $paymentIntent = $event->data->object;
    $paymentIntentId = $paymentIntent->id;

    $order = $this->orderRepository->findOneBy(['stripePaymentIntentId' => $paymentIntentId]);

    if (!$order && str_starts_with($paymentIntentId, 'pi_')) {
      $order = $this->orderRepository->findOneBy(['stripePaymentIntentId' => substr($paymentIntentId, 3)]);
    }

    if (!$order) {
      $order = $this->orderRepository->findByPartialPaymentIntentId($paymentIntentId);
    }

    if (!$order) {
      $this->logger->error('Order not found for payment intent: ' . $paymentIntentId);
      return;
    }

    $this->orderService->cancelOrder($order);
    $this->logger->info('Order cancelled due to payment failure: ' . $order->getReference());
  }
}