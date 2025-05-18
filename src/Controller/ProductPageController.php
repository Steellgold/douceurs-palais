<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour l'affichage des pages de produits
 *
 * Ce contrôleur gère l'affichage détaillé d'un produit spécifique.
 */
final class ProductPageController extends AbstractController {
  /**
   * Constructeur du contrôleur de pages de produits
   *
   * @param ProductRepository $productRepository Repository des produits
   */
  public function __construct(
    private readonly ProductRepository $productRepository
  ) {

  }

  /**
   * Affiche la page détaillée d'un produit
   *
   * @param string $slug Le slug du produit à afficher
   * @return Response Page détaillée du produit
   * @throws NotFoundHttpException Si le produit n'existe pas
   */
  #[Route('/product/{slug}', name: 'app_product_page')]
  public function index(string $slug): Response {
    $product = $this->productRepository->findOneBySlug($slug);

    if (!$product) {
      throw $this->createNotFoundException('Le produit demandé n\'existe pas');
    }

    return $this->render('product_page/index.html.twig', [
      'product' => $product
    ]);
  }
}