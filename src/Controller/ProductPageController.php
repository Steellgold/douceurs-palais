<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductPageController extends AbstractController {
  public function __construct(
    private readonly ProductRepository $productRepository
  ) {

  }

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