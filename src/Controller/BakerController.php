<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Form\BakeryType;
use App\Form\ProductType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Contrôleur pour la gestion de l'espace boulanger
 *
 * Ce contrôleur permet aux utilisateurs ayant le rôle ROLE_BAKER de :
 * - Gérer les informations de leur boulangerie
 * - Gérer leurs produits (ajouter, modifier, supprimer)
 * - Gérer leurs commandes (consulter, modifier le statut)
 */
#[Route('/baker')]
#[IsGranted('ROLE_BAKER')]
class BakerController extends AbstractController {
  /**
   * Constructeur du contrôleur boulanger
   *
   * @param SluggerInterface $slugger Service de génération de slugs
   */
  public function __construct(
    private readonly SluggerInterface $slugger
  ) {
  }

  /**
   * Affiche le tableau de bord du boulanger
   *
   * @return Response Page du tableau de bord
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('', name: 'app_baker')]
  public function index(): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    return $this->render('baker/index.html.twig', [
      'bakery' => $bakery,
    ]);
  }

  /**
   * Affiche les détails de la boulangerie gérée
   *
   * @return Response Page des détails de la boulangerie
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('/bakery', name: 'app_baker_bakery_details')]
  public function bakeryDetails(): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    return $this->render('baker/bakery-details.html.twig', [
      'bakery' => $bakery,
    ]);
  }

  /**
   * Affiche la liste des produits de la boulangerie
   *
   * @param ProductRepository $productRepository Repository des produits
   * @return Response Page listant les produits
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('/products', name: 'app_baker_products')]
  public function products(ProductRepository $productRepository): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    $products = $productRepository->findByBakery($bakery);

    return $this->render('baker/products.html.twig', [
      'bakery' => $bakery,
      'products' => $products,
    ]);
  }

  /**
   * Affiche et traite le formulaire d'ajout d'un nouveau produit
   *
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après ajout
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('/products/new', name: 'app_baker_product_new')]
  public function newProduct(Request $request, EntityManagerInterface $entityManager): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    $product = new Product();
    $product->setBakery($bakery);

    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Génération du slug à partir du nom du produit
      $product->setSlug(strtolower($this->slugger->slug($product->getName())->toString()));

      $entityManager->persist($product);
      $entityManager->flush();

      $this->addFlash('success', 'Produit ajouté avec succès.');

      return $this->redirectToRoute('app_baker_products');
    }

    return $this->render('baker/product-form.html.twig', [
      'form' => $form->createView(),
      'bakery' => $bakery,
    ]);
  }

  /**
   * Affiche et traite le formulaire de modification d'un produit
   *
   * @param Product $product Le produit à modifier
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après modification
   * @throws AccessDeniedException Si le produit n'appartient pas à la boulangerie gérée par l'utilisateur
   */
  #[Route('/products/{id}/edit', name: 'app_baker_product_edit')]
  public function editProduct(Product $product, Request $request, EntityManagerInterface $entityManager): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery || $product->getBakery() !== $bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier ce produit.');
    }

    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Mise à jour du slug à partir du nom modifié
      $product->setSlug(strtolower($this->slugger->slug($product->getName())->toString()));

      $entityManager->flush();

      $this->addFlash('success', 'Produit mis à jour avec succès.');

      return $this->redirectToRoute('app_baker_products');
    }

    return $this->render('baker/product-form.html.twig', [
      'form' => $form->createView(),
      'bakery' => $bakery,
      'product' => $product,
      'edit' => true,
    ]);
  }

  /**
   * Traite la suppression d'un produit
   *
   * @param Product $product Le produit à supprimer
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Redirection après suppression
   * @throws AccessDeniedException Si le produit n'appartient pas à la boulangerie gérée par l'utilisateur
   */
  #[Route('/products/{id}/delete', name: 'app_baker_product_delete', methods: ['POST'])]
  public function deleteProduct(Product $product, Request $request, EntityManagerInterface $entityManager): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery || $product->getBakery() !== $bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer ce produit.');
    }

    if ($this->isCsrfTokenValid('delete' . $product->getId(), $request->request->get('_token'))) {
      $entityManager->remove($product);
      $entityManager->flush();

      $this->addFlash('success', 'Produit supprimé avec succès.');
    }

    return $this->redirectToRoute('app_baker_products');
  }

  /**
   * Affiche et traite le formulaire de modification de la boulangerie
   *
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après modification
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('/bakery/edit', name: 'app_baker_bakery_edit')]
  public function editBakery(Request $request, EntityManagerInterface $entityManager): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    $form = $this->createForm(BakeryType::class, $bakery);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Mise à jour du slug à partir du nom modifié
      $bakery->setSlug(strtolower($this->slugger->slug($bakery->getName())->toString()));

      $entityManager->flush();

      $this->addFlash('success', 'Informations de la boulangerie mises à jour avec succès.');

      return $this->redirectToRoute('app_baker_bakery_details');
    }

    return $this->render('baker/bakery-edit.html.twig', [
      'form' => $form->createView(),
      'bakery' => $bakery
    ]);
  }

  /**
   * Affiche la liste des commandes contenant des produits de la boulangerie
   *
   * Permet de filtrer les commandes par statut.
   *
   * @param Request $request Requête HTTP
   * @param OrderRepository $orderRepository Repository des commandes
   * @return Response Page listant les commandes
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('/orders', name: 'app_baker_orders')]
  public function orders(Request $request, OrderRepository $orderRepository): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    $status = $request->query->get('status', 'all');
    $orders = $orderRepository->findOrdersContainingBakeryProducts($bakery, $status);

    return $this->render('baker/orders.html.twig', [
      'bakery' => $bakery,
      'orders' => $orders,
    ]);
  }

  /**
   * Affiche les détails d'une commande
   *
   * @param Order $order La commande à afficher
   * @return Response Page des détails de la commande
   * @throws AccessDeniedException Si la commande ne contient pas de produits de la boulangerie gérée
   */
  #[Route('/orders/{id}/details', name: 'app_baker_order_details')]
  public function orderDetails(Order $order): Response {
    list($bakery, $hasProductsFromBakery) = $this->extracted($order);

    if (!$hasProductsFromBakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir cette commande.');
    }

    return $this->render('baker/order_details.html.twig', [
      'bakery' => $bakery,
      'order' => $order,
    ]);
  }

  /**
   * Met à jour le statut d'une commande
   *
   * Vérifie que la transition de statut est valide selon le workflow défini.
   *
   * @param Order $order La commande à mettre à jour
   * @param string $status Le nouveau statut
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Redirection après mise à jour
   * @throws AccessDeniedException Si la commande ne contient pas de produits de la boulangerie gérée
   */
  #[Route('/orders/{id}/update-status/{status}', name: 'app_baker_order_update_status', methods: ['POST'])]
  public function updateOrderStatus(Order $order, string $status, Request $request, EntityManagerInterface $entityManager): Response {
    list($bakery, $hasProductsFromBakery) = $this->extracted($order);

    if (!$hasProductsFromBakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette commande.');
    }

    if (!$this->isCsrfTokenValid('update_status' . $order->getId(), $request->request->get('_token'))) {
      $this->addFlash('error', 'Token CSRF invalide.');
      return $this->redirectToRoute('app_baker_orders');
    }

    // Définition des transitions de statut valides
    $validTransitions = [
      'paid' => ['preparing', 'cancelled'],
      'preparing' => ['shipped', 'cancelled'],
      'shipped' => ['delivered', 'cancelled'],
    ];

    if (!isset($validTransitions[$order->getStatus()]) || !in_array($status, $validTransitions[$order->getStatus()])) {
      $this->addFlash('error', 'Transition de statut non valide.');
      return $this->redirectToRoute('app_baker_orders');
    }

    $order->setStatus($status);
    $entityManager->flush();

    $this->addFlash('success', 'Statut de la commande mis à jour avec succès.');

    return $this->redirectToRoute('app_baker_orders');
  }

  /**
   * Méthode utilitaire pour extraire la boulangerie et vérifier si une commande contient ses produits
   *
   * @param Order $order La commande à vérifier
   * @return array Tableau contenant la boulangerie et un booléen indiquant si la commande contient ses produits
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  public function extracted(Order $order): array {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    $hasProductsFromBakery = false;
    foreach ($order->getItems() as $item) {
      if ($item->getProduct()->getBakery() === $bakery) {
        $hasProductsFromBakery = true;
        break;
      }
    }
    return array($bakery, $hasProductsFromBakery);
  }
}