<?php

namespace App\Controller;

use App\Repository\BakeryRepository;
use App\Repository\ProductRepository;
use App\Service\LoyaltyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class LoyaltyController extends AbstractController {
  public function __construct(
    private readonly LoyaltyService    $loyaltyService,
    private readonly ProductRepository $productRepository,
    private readonly BakeryRepository  $bakeryRepository
  ) {
  }

  #[Route('/b/{slug}/loyalty', name: 'app_bakery_loyalty')]
  public function showBakeryLoyalty(string $slug): Response {
    $bakery = $this->bakeryRepository->findBySlug($slug);

    if (!$bakery) {
      throw $this->createNotFoundException('Cette boutique n\'existe pas');
    }

    $loyaltyProducts = $this->productRepository->findAvailableWithPointsByBakery($bakery);

    return $this->render('loyalty/bakery.html.twig', [
      'bakery' => $bakery,
      'loyaltyProducts' => $loyaltyProducts,
    ]);
  }

  #[Route('/loyalty/redeem/{id}', name: 'app_loyalty_redeem', methods: ['POST'])]
  #[IsGranted('ROLE_USER')]
  public function redeem(string $id, Request $request): Response {
    $product = $this->productRepository->find($id);

    if (!$product) {
      throw $this->createNotFoundException('Produit non trouvé');
    }

    if (!$product->isAvailableWithPoints()) {
      $this->addFlash('error', 'Ce produit n\'est pas disponible avec des points de fidélité');
      return $this->redirectToRoute('app_bakery_loyalty', ['slug' => $product->getBakery()->getSlug()]);
    }

    $user = $this->getUser();

    if ($this->loyaltyService->redeemProductWithPoints($user, $product)) {
      $this->addFlash('success', 'Vous avez utilisé vos points pour obtenir ' . $product->getName());
    } else {
      $this->addFlash('error', 'Vous n\'avez pas assez de points pour obtenir ce produit');
    }

    return $this->redirectToRoute('app_bakery_loyalty', ['slug' => $product->getBakery()->getSlug()]);
  }
}