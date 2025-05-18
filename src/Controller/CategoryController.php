<?php

namespace App\Controller;

use App\Repository\BakeryRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour l'affichage des catégories de produits
 *
 * Ce contrôleur gère l'affichage des catégories de produits,
 * ainsi que les produits d'une catégorie spécifique, avec
 * possibilité de filtrer par boulangerie.
 */
class CategoryController extends AbstractController {
  /**
   * Constructeur du contrôleur de catégories
   *
   * @param CategoryRepository $categoryRepository Repository des catégories
   * @param ProductRepository $productRepository Repository des produits
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   */
  public function __construct(
    private readonly CategoryRepository $categoryRepository,
    private readonly ProductRepository  $productRepository,
    private readonly BakeryRepository   $bakeryRepository
  ) {
  }

  /**
   * Affiche la liste de toutes les catégories
   *
   * @return Response Page listant toutes les catégories
   */
  #[Route('/categories', name: 'app_categories')]
  public function index(): Response {
    $categories = $this->categoryRepository->findWithProductCounts();

    return $this->render('category/index.html.twig', [
      'categories' => $categories,
    ]);
  }

  /**
   * Affiche les produits d'une catégorie spécifique
   *
   * Les produits sont affichés avec pagination.
   *
   * @param string $slug Le slug de la catégorie à afficher
   * @param Request $request Requête HTTP contenant les paramètres de pagination
   * @return Response Page des produits de la catégorie
   * @throws NotFoundHttpException Si la catégorie n'existe pas
   */
  #[Route('/category/{slug}', name: 'app_category_show')]
  public function show(string $slug, Request $request): Response {
    $category = $this->categoryRepository->findBySlug($slug);

    if (!$category) {
      throw $this->createNotFoundException('Cette catégorie n\'existe pas');
    }

    // Récupération des paramètres de pagination
    $page = $request->query->getInt('page', 1);
    $limit = 12;

    // Récupération des produits avec pagination
    $products = $this->productRepository->findByCategoryPaginated($category, $page, $limit);

    return $this->render('category/show.html.twig', [
      'category' => $category,
      'products' => $products,
      'current_page' => $page,
      'total_pages' => ceil(count($products) / $limit)
    ]);
  }

  /**
   * Affiche les produits d'une catégorie pour une boulangerie spécifique
   *
   * @param string $bakery_slug Le slug de la boulangerie
   * @param string $category_slug Le slug de la catégorie
   * @return Response Page des produits filtrés par boulangerie et catégorie
   * @throws NotFoundHttpException Si la boulangerie ou la catégorie n'existe pas
   */
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

    // Récupération des produits filtrés par boulangerie et catégorie
    $products = $this->productRepository->findByBakeryAndCategory($bakery, $category);

    return $this->render('category/bakery_category.html.twig', [
      'bakery' => $bakery,
      'category' => $category,
      'products' => $products,
    ]);
  }
}