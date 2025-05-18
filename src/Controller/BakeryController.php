<?php

namespace App\Controller;

use App\Entity\Bakery;
use App\Repository\BakeryRepository;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour l'affichage public des boulangeries
 *
 * Ce contrôleur gère l'affichage des pages publiques des boulangeries
 * et permet aux utilisateurs d'ajouter/retirer des boulangeries à leurs favoris.
 */
#[Route('/b')]
class BakeryController extends AbstractController {
  /**
   * Constructeur du contrôleur
   *
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   * @param ProductRepository $productRepository Repository des produits
   * @param CategoryRepository $categoryRepository Repository des catégories
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   */
  public function __construct(
    private readonly BakeryRepository $bakeryRepository,
    private readonly ProductRepository $productRepository,
    private readonly CategoryRepository $categoryRepository,
    private readonly EntityManagerInterface $entityManager
  ) {
  }

  /**
   * Affiche la page d'une boulangerie avec ses produits et catégories
   *
   * @param string $slug Le slug de la boulangerie à afficher
   * @return Response Page de la boulangerie
   * @throws NotFoundHttpException Si la boulangerie n'existe pas
   */
  #[Route('/{slug}', name: 'app_bakery_show')]
  public function show(string $slug): Response {
    $bakery = $this->bakeryRepository->findBySlug($slug);

    if (!$bakery) {
      throw $this->createNotFoundException('Cette boutique n\'existe pas');
    }

    // Récupération des produits populaires
    $popularProducts = $this->productRepository->findMostPopularByBakery($bakery, 3);
    // Récupération de tous les produits
    $allProducts = $this->productRepository->findByBakery($bakery);
    // Récupération des catégories avec leurs produits
    $categories = $this->categoryRepository->findCategoriesWithProductsByBakery($bakery->getId());

    return $this->render('bakery/show.html.twig', [
      'bakery' => $bakery,
      'popularProducts' => $popularProducts,
      'allProducts' => $allProducts,
      'categories' => $categories,
    ]);
  }

  /**
   * Ajoute ou retire une boulangerie des favoris de l'utilisateur
   *
   * @param Bakery $bakery La boulangerie à ajouter/retirer des favoris
   * @param Request $request Requête HTTP
   * @return Response Redirection après la modification
   */
  #[Route('/{id}/favorite', name: 'app_bakery_toggle_favorite', methods: ['POST'])]
  public function toggleFavorite(Bakery $bakery, Request $request): Response {
    $user = $this->getUser();

    // Redirection vers la page de connexion si l'utilisateur n'est pas connecté
    if (!$user) {
      return $this->redirectToRoute('app_login');
    }

    // Vérification du token CSRF
    if ($this->isCsrfTokenValid('favorite-bakery' . $bakery->getId(), $request->request->get('_token'))) {
      // Basculement du statut de favori
      if ($user->hasFavoriteBakery($bakery)) {
        $user->removeFavoriteBakery($bakery);
        $this->addFlash('success', 'La boutique a été retirée de vos favoris');
      } else {
        $user->addFavoriteBakery($bakery);
        $this->addFlash('success', 'La boutique a été ajoutée à vos favoris');
      }

      $this->entityManager->flush();
    }

    // Redirection vers la page référente ou la page de la boulangerie
    $referer = $request->headers->get('referer');
    return $this->redirect($referer ?: $this->generateUrl('app_bakery_show', ['slug' => $bakery->getSlug()]));
  }
}