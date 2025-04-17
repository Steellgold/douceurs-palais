<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductPageController extends AbstractController {
  public function __construct(
    private readonly ProductRepository $productRepository
    ) {
      
    }
    
    #[Route('/product/{slug}', name: 'app_product_page')]
    public function index(string $slug): Response {
      $product = $this->productRepository->findOneBySlug($slug);
      
      if (!$product) {
        throw $this->createNotFoundException('Le produit demandé n\'existe pas');
      }
      
      return $this->render('product_page/index.html.twig', [
        'product' => $product
      ]);
    }
    
    // #[Route('/product/{id}', name: 'app_product_page')]
    // public function index(): Response {
    //   return $this->render('product_page/index.html.twig', [
    //     'product' => [
    //       'name' => 'La Mie Dorée',
    //       'description' => 'Pain artisanal à croûte dorée et craquante. Mie aérée et moelleuse aux notes de céréales. Fermentation lente pour un goût authentique. Parfait pour tous vos repas.',
    //       'price' => 2.50,
    //       'images' => [
    //         'https://cdn.douceurs-palais.fr/products/f9878fc8-a103-49b4-9e2b-1ef3f1757b98.webp',
    //         'https://cdn.douceurs-palais.fr/products/543f933b-3913-4680-8221-6874d907f406.webp',
    //         'https://cdn.douceurs-palais.fr/products/38c22ef4-bb89-442e-9893-b4f2c69ceb63.webp'
    //       ],
    //       'ingredients' => [
    //         'Farine de blé',
    //         'Eau',
    //         'Levain naturel',
    //         'Sel'
    //       ],
    //       'allergenes' => [
    //         'Gluten'
    //       ],
    //       'nutriscore' => 'A',
    //       'nutritional_values' => [
    //         'calories' => 220,
    //         'fat' => 1,
    //         'carbohydrates' => 45,
    //         'proteins' => 7
    //       ],
    //       'conservation' => 'Conserver dans un endroit frais et sec, à l’abri de la lumière. Se consomme de préférence dans les 3 jours suivant l\'achat.',
    //       'pairings' => [
    //         'Fromage de chèvre',
    //         'Confiture de figues',
    //         'Jambon cru',
    //         'Soupe de légumes'
    //       ],
    //     ]
    //   ]);
    // }
  }
  