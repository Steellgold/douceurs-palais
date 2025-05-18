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
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\ByteString;

/**
 * Contrôleur gérant le processus de paiement et finalisation de commande
 *
 * Ce contrôleur gère toutes les étapes liées au processus de paiement :
 * - Affichage de la page de commande
 * - Traitement du formulaire de commande (utilisateur connecté ou invité)
 * - Redirection vers Stripe pour le paiement
 * - Gestion des retours de paiement (succès ou échec)
 * - Affichage des détails de commande
 */
#[Route('/checkout')]
class CheckoutController extends AbstractController {
  /**
   * Constructeur du contrôleur de commande
   *
   * @param CartService $cartService Service de gestion du panier
   * @param OrderService $orderService Service de gestion des commandes
   * @param StripeService $stripeService Service de gestion des paiements Stripe
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @param OrderRepository $orderRepository Repository des commandes
   * @param UserPasswordHasherInterface $passwordHasher Service de hachage des mots de passe
   */
  public function __construct(
    private readonly CartService                 $cartService,
    private readonly OrderService                $orderService,
    private readonly StripeService               $stripeService,
    private readonly EntityManagerInterface      $entityManager,
    private readonly OrderRepository             $orderRepository,
    private readonly UserPasswordHasherInterface $passwordHasher
  ) {
  }

  /**
   * Affiche et traite la page de commande
   *
   * Gère deux cas distincts :
   * 1. Utilisateur connecté : formulaire avec ses adresses enregistrées
   * 2. Utilisateur invité : formulaire avec création de compte implicite
   *
   * Dans les deux cas, crée une commande et redirige vers Stripe pour le paiement.
   *
   * @param Request $request Requête HTTP
   * @return Response Réponse HTTP (formulaire ou redirection)
   */
  #[Route('', name: 'app_checkout')]
  public function index(Request $request): Response {
    $cart = $this->cartService->getCart();

    // Vérification que le panier n'est pas vide
    if ($cart->isEmpty()) {
      $this->addFlash('error', 'Votre panier est vide');
      return $this->redirectToRoute('app_cart_index');
    }

    // Vérification que le panier ne contient pas des produits de plusieurs boutiques
    if ($cart->hasMultipleShops()) {
      return $this->redirectToRoute('app_cart_select_shop');
    }

    $user = $this->getUser();

    // Traitement pour un utilisateur connecté
    if ($user) {
      $form = $this->createForm(CheckoutType::class, null, [
        'user' => $user,
      ]);

      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
        $formData = $form->getData();

        $shippingAddress = $formData['shipping_address'];

        // Si l'utilisateur n'a pas d'adresse principale, définit celle-ci comme principale
        if (!$user->getPrimaryAddress()) {
          foreach ($user->getAddresses() as $address) {
            $address->setIsPrimary(false);
          }

          $shippingAddress->setIsPrimary(true);
          $this->entityManager->flush();
        }

        // Création de la commande à partir du panier
        $order = $this->orderService->createOrderFromCart(
          $cart,
          $formData['shipping_address'],
          $formData['different_billing_address'] ? $formData['billing_address'] : null
        );

        try {
          // Création de la session de paiement Stripe
          $session = $this->stripeService->createCheckoutSession($order);
          $this->entityManager->flush();

          // Redirection vers la page de paiement Stripe
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
    }
    // Traitement pour un utilisateur invité
    else {
      $form = $this->createForm(CheckoutGuestType::class);

      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
        $formData = $form->getData();

        // Création d'un nouvel utilisateur avec les informations du formulaire
        $user = new User();
        $user->setEmail($formData['email']);
        $user->setFirstName($formData['firstName']);
        $user->setLastName($formData['lastName']);
        $user->setPhone($formData['phone'] ?? null);

        // Génération d'un mot de passe aléatoire
        $randomPassword = ByteString::fromRandom(12)->toString();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $randomPassword);
        $user->setPassword($hashedPassword);

        // Configuration des adresses
        $shippingAddress = $formData['shipping_address'];
        $shippingAddress->setIsPrimary(true); // Toujours définir l'adresse de livraison comme principale
        $user->addAddress($shippingAddress);

        if ($formData['different_billing_address'] && $formData['billing_address']) {
          $billingAddress = $formData['billing_address'];
          $billingAddress->setIsPrimary(false); // L'adresse de facturation n'est jamais principale si elle est différente
          $user->addAddress($billingAddress);
        }

        // Persistance de l'utilisateur en base de données
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Association du panier à l'utilisateur
        $cart->setUser($user);
        $this->entityManager->flush();

        // Création de la commande
        $order = $this->orderService->createOrderFromCart(
          $cart,
          $shippingAddress,
          $formData['different_billing_address'] ? $formData['billing_address'] : null
        );

        try {
          // Création de la session de paiement Stripe
          $session = $this->stripeService->createCheckoutSession($order);
          $this->entityManager->flush();

          // Redirection vers la page de paiement Stripe
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

  /**
   * Gère le retour après un paiement réussi sur Stripe
   *
   * Vérifie que le paiement a bien été effectué auprès de Stripe
   * et finalise la commande si c'est le cas.
   *
   * @param Request $request Requête HTTP contenant l'ID de session Stripe
   * @return Response Réponse HTTP (page de succès ou redirection)
   */
  #[Route('/success', name: 'app_checkout_success')]
  public function success(Request $request): Response {
    $sessionId = $request->query->get('session_id');
    if (!$sessionId) return $this->redirectToRoute('app_cart_index');

    // Récupération de la commande via l'ID de session Stripe
    $order = $this->orderService->getOrderByStripeSessionId($sessionId);

    if (!$order) {
      $this->addFlash('error', 'Commande introuvable');
      return $this->redirectToRoute('app_cart_index');
    }

    try {
      // Vérification du paiement auprès de Stripe
      $paymentSuccessful = $this->stripeService->verifyPayment($order);

      if ($paymentSuccessful) {
        // Finalisation de la commande
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

  /**
   * Gère l'annulation d'une commande
   *
   * Annule la commande et redirige vers le panier.
   *
   * @param string $order_id Identifiant de la commande à annuler
   * @return Response Redirection vers le panier
   */
  #[Route('/cancel/{order_id}', name: 'app_checkout_cancel')]
  public function cancel(string $order_id): Response {
    $order = $this->orderRepository->find($order_id);

    if ($order) {
      $this->orderService->cancelOrder($order);
      $this->addFlash('info', 'Votre commande a été annulée');
    }

    return $this->redirectToRoute('app_cart_index');
  }

  /**
   * Affiche les détails d'une commande
   *
   * Vérifie que l'utilisateur est autorisé à voir la commande.
   * Pour les utilisateurs non connectés, vérifie le jeton de la commande.
   *
   * @param Request $request Requête HTTP
   * @param Order $order La commande à afficher
   * @return Response Page des détails de la commande
   * @throws AccessDeniedException Si l'utilisateur n'est pas autorisé
   */
  #[Route('/order/{id}', name: 'app_checkout_order_details')]
  public function orderDetails(Request $request, Order $order): Response {
    $currentUser = $this->getUser();

    // Vérification des droits d'accès pour un utilisateur connecté
    if ($currentUser && $order->getUser() !== $currentUser) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à cette commande');
    }

    // Vérification des droits d'accès pour un utilisateur non connecté (via jeton)
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