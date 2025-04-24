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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/baker')]
#[IsGranted('ROLE_BAKER')]
class BakerController extends AbstractController {
  public function __construct(
    private readonly SluggerInterface $slugger
  ) {
  }

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
   * @param Order $order
   * @return array
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