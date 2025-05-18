<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\BakeryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Contrôleur pour la génération du sitemap XML
 *
 * Ce contrôleur génère un sitemap XML dynamique contenant les URLs
 * des pages principales du site, des produits et des boulangeries,
 * pour faciliter l'indexation par les moteurs de recherche.
 */
class SitemapController extends AbstractController {
  /**
   * Constructeur du contrôleur de sitemap
   *
   * @param ProductRepository $productRepository Repository des produits
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   * @param UrlGeneratorInterface $urlGenerator Générateur d'URL
   */
  public function __construct(
    private readonly ProductRepository     $productRepository,
    private readonly BakeryRepository      $bakeryRepository,
    private readonly UrlGeneratorInterface $urlGenerator
  ) {
  }

  /**
   * Génère le sitemap XML
   *
   * Le sitemap contient :
   * - La page d'accueil
   * - Les pages de produits avec leur date de dernière modification
   * - Les pages de boulangeries avec leur date de dernière modification
   *
   * @return Response Sitemap au format XML
   */
  #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'], format: 'xml')]
  public function index(): Response {
    $urls = [];

    // Ajout de la page d'accueil
    $urls[] = [
      'loc' => str_replace('http://', 'https://',
        $this->urlGenerator->generate('app_index_page', [], UrlGeneratorInterface::ABSOLUTE_URL)
      ),
      'priority' => '1.0',
      'changefreq' => 'daily'
    ];

    // Ajout des pages de produits
    $products = $this->productRepository->findAll();
    foreach ($products as $product) {
      $urls[] = [
        'loc' => str_replace('http://', 'https://',
          $this->urlGenerator->generate('app_product_page', ['slug' => $product->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL)
        ),
        'priority' => '0.7',
        'changefreq' => 'weekly',
        'lastmod' => $product->getUpdatedAt() ?? $product->getCreatedAt()
      ];
    }

    // Ajout des pages de boulangeries
    $bakeries = $this->bakeryRepository->findAll();
    foreach ($bakeries as $bakery) {
      $urls[] = [
        'loc' => str_replace('http://', 'https://',
          $this->urlGenerator->generate('app_bakery_show', ['slug' => $bakery->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL)
        ),
        'priority' => '0.8',
        'changefreq' => 'weekly',
        'lastmod' => $bakery->getUpdatedAt() ?? $bakery->getCreatedAt()
      ];
    }

    // Génération de la réponse XML
    $response = new Response(
      $this->renderView('sitemap/index.xml.twig', ['urls' => $urls]),
      200,
      ['Content-Type' => 'text/xml']
    );

    return $response;
  }
}