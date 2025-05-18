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

/**
 * Contrôleur pour la gestion du programme de fidélité
 *
 * Ce contrôleur gère l'affichage des produits disponibles avec des points
 * de fidélité et permet aux utilisateurs d'échanger leurs points contre
 * des produits.
 */
class LoyaltyController extends AbstractController {
  /**
   * Constructeur du contrôleur de fidélité
   *
   * @param LoyaltyService $loyaltyService Service de gestion de la fidélité
   * @param ProductRepository $productRepository Repository des produits
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   */
  public function __construct(
    private readonly LoyaltyService    $loyaltyService,
    private readonly ProductRepository $productRepository,
    private readonly BakeryRepository  $bakeryRepository
  ) {
  }

  /**
   * Affiche la page des produits disponibles avec des points de fidélité pour une boulangerie
   *
   * @param string $slug Le slug de la boulangerie
   * @return Response Page des produits disponibles avec des points
   * @throws NotFoundHttpException Si la boulangerie n'existe pas
   */
  #[Route('/b/{slug}/loyalty', name: 'app_bakery_loyalty')]
  public function showBakeryLoyalty(string $slug): Response {
    $bakery = $this->bakeryRepository->findBySlug($slug);

    if (!$bakery) {
      throw $this->createNotFoundException('Cette boutique n\'existe pas');
    }

    // Récupération des produits disponibles avec des points pour cette boulangerie
    $loyaltyProducts = $this->productRepository->findAvailableWithPointsByBakery($bakery);

    return $this->render('loyalty/bakery.html.twig', [
      'bakery' => $bakery,
      'loyaltyProducts' => $loyaltyProducts,
    ]);
  }

  /**
   * Traite l'échange de points contre un produit
   *
   * L'utilisateur doit être connecté pour échanger des points.
   *
   * @param string $id Identifiant du produit à obtenir
   * @param Request $request Requête HTTP
   * @return Response Redirection vers la page de fidélité de la boulangerie
   * @throws NotFoundHttpException Si le produit n'existe pas
   * @throws AccessDeniedException Si l'utilisateur n'est pas connecté
   */
  #[Route('/loyalty/redeem/{id}', name: 'app_loyalty_redeem', methods: ['POST'])]
  #[IsGranted('ROLE_USER')]
  public function redeem(string $id, Request $request): Response {
    $product = $this->productRepository->find($id);

    if (!$product) {
      throw $this->createNotFoundException('Produit non trouvé');
    }

    // Vérification que le produit est disponible avec des points
    if (!$product->isAvailableWithPoints()) {
      $this->addFlash('error', 'Ce produit n\'est pas disponible avec des points de fidélité');
      return $this->redirectToRoute('app_bakery_loyalty', ['slug' => $product->getBakery()->getSlug()]);
    }

    $user = $this->getUser();

    // Tentative d'échange de points contre le produit
    if ($this->loyaltyService->redeemProductWithPoints($user, $product)) {
      $this->addFlash('success', 'Vous avez utilisé vos points pour obtenir ' . $product->getName());
    } else {
      $this->addFlash('error', 'Vous n\'avez pas assez de points pour obtenir ce produit');
    }

    return $this->redirectToRoute('app_bakery_loyalty', ['slug' => $product->getBakery()->getSlug()]);
  }
}