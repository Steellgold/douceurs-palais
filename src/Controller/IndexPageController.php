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

    $popularProducts = $this->productRepository->findMostPopular();
    return $this->render('index_page/index.html.twig', [
      'popularProducts' => $popularProducts
    ]);
  }
}