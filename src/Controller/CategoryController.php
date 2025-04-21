<?php

namespace App\Controller;

use App\Repository\BakeryRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CategoryController extends AbstractController {
  public function __construct(
    private readonly CategoryRepository $categoryRepository,
    private readonly ProductRepository  $productRepository,
    private readonly BakeryRepository   $bakeryRepository
  ) {
  }

  #[Route('/categories', name: 'app_categories')]
  public function index(): Response {
    $categories = $this->categoryRepository->findWithProductCounts();

    return $this->render('category/index.html.twig', [
      'categories' => $categories,
    ]);
  }

  #[Route('/category/{slug}', name: 'app_category_show')]
  public function show(string $slug, Request $request): Response {
    $category = $this->categoryRepository->findBySlug($slug);

    if (!$category) {
      throw $this->createNotFoundException('Cette catégorie n\'existe pas');
    }

    $page = $request->query->getInt('page', 1);
    $limit = 12;

    $products = $this->productRepository->findByCategoryPaginated($category, $page, $limit);

    return $this->render('category/show.html.twig', [
      'category' => $category,
      'products' => $products,
      'current_page' => $page,
      'total_pages' => ceil(count($products) / $limit)
    ]);
  }

  #[Route('/b/{bakery_slug}/category/{category_slug}', name: 'app_bakery_category')]
  public function bakeryCategory(string $bakery_slug, string $category_slug): Response {
    $bakery = $this->bakeryRepository->findBySlug($bakery_slug);
    $category = $this->categoryRepository->findBySlug($category_slug);

    if (!$bakery) {
      throw $this->createNotFoundException('Cette boulangerie n\'existe pas');
    }

    if (!$category) {
      throw $this->createNotFoundException('Cette catégorie n\'existe pas');
    }

    $products = $this->productRepository->findByBakeryAndCategory($bakery, $category);

    return $this->render('category/bakery_category.html.twig', [
      'bakery' => $bakery,
      'category' => $category,
      'products' => $products,
    ]);
  }
}