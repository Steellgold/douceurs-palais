<?php

namespace App\Controller;

use App\Repository\BakeryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la page d'accueil et la liste des boulangeries
 *
 * Ce contrôleur gère l'affichage de la page d'accueil qui présente
 * soit les produits populaires et les boulangeries populaires (utilisateur non connecté),
 * soit les boulangeries favorites de l'utilisateur (utilisateur connecté).
 */
final class IndexPageController extends AbstractController {
  /**
   * Constructeur du contrôleur de la page d'accueil
   *
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   * @param ProductRepository $productRepository Repository des produits
   */
  public function __construct(
    private readonly BakeryRepository  $bakeryRepository,
    private readonly ProductRepository $productRepository
  ) {
  }

  /**
   * Affiche la page d'accueil
   *
   * Pour un utilisateur connecté avec des boulangeries favorites,
   * affiche une page personnalisée avec ses favoris.
   * Sinon, affiche les produits et boulangeries populaires.
   *
   * @return Response Page d'accueil personnalisée ou par défaut
   */
  #[Route('/', name: 'app_index_page')]
  public function index(): Response {
    $user = $this->getUser();

    // Récupération des produits et boulangeries populaires (pour tous les utilisateurs)
    $popularProducts = $this->productRepository->findMostPopular();
    $popularBakeries = $this->bakeryRepository->findPopularBakeries(3);

    // Si l'utilisateur est connecté et a des favoris, afficher une page personnalisée
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

    // Affichage par défaut (utilisateur non connecté ou sans favoris)
    return $this->render('index_page/index.html.twig', [
      'popularProducts' => $popularProducts,
      'popularBakeries' => $popularBakeries
    ]);
  }

  /**
   * Affiche la liste de toutes les boulangeries
   *
   * @return Response Page listant toutes les boulangeries
   */
  #[Route('/all', name: 'app_bakery_list')]
  public function list(): Response {
    $bakeries = $this->bakeryRepository->findAll();

    return $this->render('bakery/list.html.twig', [
      'bakeries' => $bakeries,
    ]);
  }
}