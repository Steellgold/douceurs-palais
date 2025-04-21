<?php

namespace App\Controller;

use App\Entity\Bakery;
use App\Repository\BakeryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/b')]
class BakeryController extends AbstractController {
  public function __construct(
    private readonly BakeryRepository $bakeryRepository,
    private readonly ProductRepository $productRepository,
    private readonly EntityManagerInterface $entityManager
  ) {
  }

  #[Route('/{slug}', name: 'app_bakery_show')]
  public function show(string $slug): Response {
    $bakery = $this->bakeryRepository->findBySlug($slug);

    if (!$bakery) {
      throw $this->createNotFoundException('Cette boutique n\'existe pas');
    }

    $popularProducts = $this->productRepository->findMostPopularByBakery($bakery, 3);
    $allProducts = $this->productRepository->findByBakery($bakery);

    return $this->render('bakery/show.html.twig', [
      'bakery' => $bakery,
      'popularProducts' => $popularProducts,
      'allProducts' => $allProducts,
    ]);
  }

  #[Route('/{id}/favorite', name: 'app_bakery_toggle_favorite', methods: ['POST'])]
  public function toggleFavorite(Bakery $bakery, Request $request): Response {
    $user = $this->getUser();

    if (!$user) {
      return $this->redirectToRoute('app_login');
    }

    if ($this->isCsrfTokenValid('favorite-bakery' . $bakery->getId(), $request->request->get('_token'))) {
      if ($user->hasFavoriteBakery($bakery)) {
        $user->removeFavoriteBakery($bakery);
        $this->addFlash('success', 'La boutique a été retirée de vos favoris');
      } else {
        $user->addFavoriteBakery($bakery);
        $this->addFlash('success', 'La boutique a été ajoutée à vos favoris');
      }

      $this->entityManager->flush();
    }

    $referer = $request->headers->get('referer');
    return $this->redirect($referer ?: $this->generateUrl('app_bakery_show', ['slug' => $bakery->getSlug()]));
  }
}