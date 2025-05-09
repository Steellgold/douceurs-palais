<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Form\CheckoutGuestType;
use App\Form\CheckoutType;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\OrderService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Exception\ApiErrorException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\ByteString;

#[Route('/checkout')]
class CheckoutController extends AbstractController {
  public function __construct(
    private readonly CartService                 $cartService,
    private readonly OrderService                $orderService,
    private readonly StripeService               $stripeService,
    private readonly EntityManagerInterface      $entityManager,
    private readonly OrderRepository             $orderRepository,
    private readonly UserPasswordHasherInterface $passwordHasher
  ) {
  }

  #[Route('', name: 'app_checkout')]
  public function index(Request $request): Response {
    $cart = $this->cartService->getCart();

    if ($cart->isEmpty()) {
      $this->addFlash('error', 'Votre panier est vide');
      return $this->redirectToRoute('app_cart_index');
    }

    if ($cart->hasMultipleShops()) {
      return $this->redirectToRoute('app_cart_select_shop');
    }

    $user = $this->getUser();

    if ($user) {
      $form = $this->createForm(CheckoutType::class, null, [
        'user' => $user,
      ]);

      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
        $formData = $form->getData();

        $shippingAddress = $formData['shipping_address'];

        if (!$user->getPrimaryAddress()) {
          foreach ($user->getAddresses() as $address) {
            $address->setIsPrimary(false);
          }

          $shippingAddress->setIsPrimary(true);
          $this->entityManager->flush();
        }

        $order = $this->orderService->createOrderFromCart(
          $cart,
          $formData['shipping_address'],
          $formData['different_billing_address'] ? $formData['billing_address'] : null
        );

        try {
          $session = $this->stripeService->createCheckoutSession($order);
          $this->entityManager->flush();

          return $this->redirect($session->url);
        } catch (ApiErrorException $e) {
          $this->addFlash('error', 'Une erreur est survenue lors de la création de la session de paiement: ' . $e->getMessage());
          return $this->redirectToRoute('app_checkout');
        }
      }

      return $this->render('checkout/index.html.twig', [
        'cart' => $cart,
        'form' => $form->createView(),
      ]);
    } else {
      $form = $this->createForm(CheckoutGuestType::class);

      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
        $formData = $form->getData();

        $user = new User();
        $user->setEmail($formData['email']);
        $user->setFirstName($formData['firstName']);
        $user->setLastName($formData['lastName']);
        $user->setPhone($formData['phone'] ?? null);

        $randomPassword = ByteString::fromRandom(12)->toString();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        $shippingAddress = $formData['shipping_address'];
        $shippingAddress->setIsPrimary(true); // Toujours définir l'adresse de livraison comme principale
        $user->addAddress($shippingAddress);

        if ($formData['different_billing_address'] && $formData['billing_address']) {
          $billingAddress = $formData['billing_address'];
          $billingAddress->setIsPrimary(false); // L'adresse de facturation n'est jamais principale si elle est différente
          $user->addAddress($billingAddress);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $cart->setUser($user);
        $this->entityManager->flush();

        $order = $this->orderService->createOrderFromCart(
          $cart,
          $shippingAddress,
          $formData['different_billing_address'] ? $formData['billing_address'] : null
        );

        try {
          $session = $this->stripeService->createCheckoutSession($order);
          $this->entityManager->flush();

          return $this->redirect($session->url);
        } catch (ApiErrorException $e) {
          $this->addFlash('error', 'Une erreur est survenue lors de la création de la session de paiement: ' . $e->getMessage());
          return $this->redirectToRoute('app_checkout');
        }
      }

      return $this->render('checkout/guest.html.twig', [
        'cart' => $cart,
        'form' => $form->createView(),
      ]);
    }
  }

  #[Route('/success', name: 'app_checkout_success')]
  public function success(Request $request): Response {
    $sessionId = $request->query->get('session_id');
    if (!$sessionId) return $this->redirectToRoute('app_cart_index');

    $order = $this->orderService->getOrderByStripeSessionId($sessionId);

    if (!$order) {
      $this->addFlash('error', 'Commande introuvable');
      return $this->redirectToRoute('app_cart_index');
    }

    try {
      $paymentSuccessful = $this->stripeService->verifyPayment($order);

      if ($paymentSuccessful) {
        $this->orderService->completeOrder($order);

        return $this->render('checkout/success.html.twig', [
          'order' => $order,
        ]);
      } else {
        $this->addFlash('error', 'Le paiement n\'a pas été validé');
        return $this->redirectToRoute('app_checkout');
      }
    } catch (ApiErrorException $e) {
      $this->addFlash('error', 'Une erreur est survenue lors de la vérification du paiement: ' . $e->getMessage());
      return $this->redirectToRoute('app_checkout');
    }
  }

  #[Route('/cancel/{order_id}', name: 'app_checkout_cancel')]
  public function cancel(string $order_id): Response {
    $order = $this->orderRepository->find($order_id);

    if ($order) {
      $this->orderService->cancelOrder($order);
      $this->addFlash('info', 'Votre commande a été annulée');
    }

    return $this->redirectToRoute('app_cart_index');
  }

  #[Route('/order/{id}', name: 'app_checkout_order_details')]
  public function orderDetails(Request $request, Order $order): Response {
    $currentUser = $this->getUser();

    if ($currentUser && $order->getUser() !== $currentUser) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette commande');
    }

    if (!$currentUser) {
      $orderToken = $request->query->get('token');

      if (!$orderToken || $orderToken !== $order->getToken()) {
        throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette commande');
      }
    }

    return $this->render('checkout/order_details.html.twig', [
      'order' => $order,
    ]);
  }
}