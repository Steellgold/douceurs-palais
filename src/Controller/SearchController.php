<?php

namespace App\Controller;

use App\Repository\BakeryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la recherche de boulangeries par localisation
 *
 * Ce contrôleur gère la recherche de boulangeries par code postal ou nom de ville,
 * avec auto-complétion via l'API du gouvernement français.
 */
#[Route('/search')]
class SearchController extends AbstractController {
  /**
   * Constructeur du contrôleur de recherche
   *
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   */
  public function __construct(
    private readonly BakeryRepository $bakeryRepository
  ) {
  }

  /**
   * API d'auto-complétion pour les villes
   *
   * Utilise l'API du gouvernement français pour suggérer des villes
   * en fonction du terme de recherche saisi.
   *
   * @param Request $request Requête HTTP contenant le terme de recherche
   * @return JsonResponse Réponse JSON avec les suggestions de villes
   */
  #[Route('/api/cities', name: 'app_search_api_cities', methods: ['GET'])]
  public function apiCities(Request $request): JsonResponse {
    $query = $request->query->get('q', '');

    if (strlen($query) < 2) {
      return $this->json([]);
    }

    try {
      $client = HttpClient::create();

      // Si c'est un code postal (5 chiffres), recherche par code postal
      if (preg_match('/^\d{5}$/', $query)) {
        $response = $client->request('GET', 'https://geo.api.gouv.fr/communes', [
          'query' => [
            'codePostal' => $query,
            'fields' => 'nom,code,codesPostaux',
            'limit' => 10
          ]
        ]);
      } else {
        // Sinon, recherche par nom
        $response = $client->request('GET', 'https://geo.api.gouv.fr/communes', [
          'query' => [
            'nom' => $query,
            'fields' => 'nom,code,codesPostaux',
            'limit' => 10
          ]
        ]);
      }

      $data = $response->toArray();
      $suggestions = [];

      foreach ($data as $commune) {
        // Pour chaque commune, ajouter une suggestion par code postal
        if (isset($commune['codesPostaux']) && is_array($commune['codesPostaux'])) {
          foreach ($commune['codesPostaux'] as $codePostal) {
            $suggestions[] = [
              'value' => $codePostal . ' ' . $commune['nom'],
              'label' => $commune['nom'] . ' (' . $codePostal . ')',
              'city' => $commune['nom'],
              'postalCode' => $codePostal,
              'code' => $commune['code']
            ];
          }
        }
      }

      return $this->json($suggestions);
    } catch (\Exception $e) {
      return $this->json([]);
    }
  }

  /**
   * Page de résultats de recherche
   *
   * Affiche les boulangeries trouvées dans la localisation recherchée.
   *
   * @param Request $request Requête HTTP contenant les paramètres de recherche
   * @return Response Page de résultats
   */
  #[Route('/results', name: 'app_search_results')]
  public function results(Request $request): Response {
    $location = $request->query->get('location', '');
    $city = $request->query->get('city', '');
    $postalCode = $request->query->get('postalCode', '');

    $bakeries = [];
    $searchPerformed = false;

    if ($city || $postalCode) {
      $searchPerformed = true;
      $bakeries = $this->bakeryRepository->findByLocation($city, $postalCode);
    } elseif ($location) {
      $searchPerformed = true;
      // Extraire le code postal et la ville de la chaîne location
      if (preg_match('/^(\d{5})\s+(.+)$/', $location, $matches)) {
        $postalCode = $matches[1];
        $city = $matches[2];
        $bakeries = $this->bakeryRepository->findByLocation($city, $postalCode);
      }
    }

    return $this->render('search/results.html.twig', [
      'bakeries' => $bakeries,
      'location' => $location,
      'city' => $city,
      'postalCode' => $postalCode,
      'searchPerformed' => $searchPerformed
    ]);
  }
}