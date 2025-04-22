<?php

namespace App\Controller;

use App\Repository\BakeryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class IndexPageController extends AbstractController {
  public function __construct(
    private readonly BakeryRepository  $bakeryRepository,
    private readonly ProductRepository $productRepository
  ) {
  }

  #[Route('/', name: 'app_index_page')]
  public function index(): Response {
    $user = $this->getUser();

    $popularProducts = $this->productRepository->findMostPopular();
    $popularBakeries = $this->bakeryRepository->findPopularBakeries(3);

    if ($user) {
      $favoriteCount = $this->bakeryRepository->countFavoritesByUser($user);

      if ($favoriteCount > 0) {
        $favoriteBakeries = $this->bakeryRepository->findFavoritesByUser($user, null);
        $productsFromFavorites = $this->productRepository->findFromFavoriteBakeries($user, 12);

        return $this->render('index_page/favorites.html.twig', [
          'favoriteBakeries' => $favoriteBakeries,
          'products' => $productsFromFavorites
        ]);
      }
    }

    return $this->render('index_page/index.html.twig', [
      'popularProducts' => $popularProducts,
      'popularBakeries' => $popularBakeries
    ]);
  }

  #[Route('/all', name: 'app_bakery_list')]
  public function list(): Response {
    $bakeries = $this->bakeryRepository->findAll();

    return $this->render('bakery/list.html.twig', [
      'bakeries' => $bakeries,
    ]);
  }
}