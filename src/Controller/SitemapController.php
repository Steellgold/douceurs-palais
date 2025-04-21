<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\BakeryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController {
  public function __construct(
    private ProductRepository     $productRepository,
    private BakeryRepository      $bakeryRepository,
    private UrlGeneratorInterface $urlGenerator
  ) {
  }

  #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'], format: 'xml')]
  public function index(): Response {
    $urls = [];
    dump($this->productRepository->findAll());
    dump($this->bakeryRepository->findAll());

    $urls[] = [
      'loc' => $this->urlGenerator->generate('app_index_page', [], UrlGeneratorInterface::ABSOLUTE_URL),
      'priority' => '1.0',
      'changefreq' => 'daily'
    ];

    $products = $this->productRepository->findAll();
    foreach ($products as $product) {
      $urls[] = [
        'loc' => $this->urlGenerator->generate('app_product_page', ['slug' => $product->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
        'priority' => '0.7',
        'changefreq' => 'weekly',
        'lastmod' => $product->getUpdatedAt() ?? $product->getCreatedAt()
      ];
    }

    $bakeries = $this->bakeryRepository->findAll();
    foreach ($bakeries as $bakery) {
      $urls[] = [
        'loc' => $this->urlGenerator->generate('app_bakery_show', ['slug' => $bakery->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL),
        'priority' => '0.8',
        'changefreq' => 'weekly',
        'lastmod' => $bakery->getUpdatedAt() ?? $bakery->getCreatedAt()
      ];
    }

    $response = new Response(
      $this->renderView('sitemap/index.xml.twig', ['urls' => $urls]),
      200,
      ['Content-Type' => 'text/xml']
    );

    return $response;
  }
}