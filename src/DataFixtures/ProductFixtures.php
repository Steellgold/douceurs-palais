<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Bakery;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductFixtures extends Fixture implements DependentFixtureInterface {
  private SluggerInterface $slugger;
  private array $bakeries = [];

  public function __construct(SluggerInterface $slugger) {
    $this->slugger = $slugger;
  }

  public function load(ObjectManager $manager): void {
    $this->bakeries = $manager->getRepository(Bakery::class)->findAll();

    if (empty($this->bakeries)) {
      throw new \Exception('Aucune boulangerie trouvée. Assurez-vous que BakeryFixtures a été exécuté avant.');
    }

    $products = [
      // Le Fournil Doré
      [
        'bakery_name' => 'Le Fournil Doré',
        'name' => 'Baguette Tradition',
        'description' => 'Baguette croustillante, idéale pour tous les repas.',
        'price' => 1.20,
        'category_ref' => 'category-pains',
        'ingredients' => ['Farine de blé', 'eau', 'levure', 'sel'],
        'allergenes' => ['gluten'],
        'nutritionalValues' => [
          'calories' => 250,
          'fat' => 1,
          'carbohydrates' => 52,
          'proteins' => 8
        ],
        'conservation' => 'À conserver à température ambiante, consommer dans la journée.',
        'popularity' => 85
      ],
      [
        'bakery_name' => 'Le Fournil Doré',
        'name' => 'Croissant au Beurre',
        'description' => 'Croissant feuilleté, riche et fondant.',
        'price' => 1.00,
        'category_ref' => 'category-viennoiseries',
        'ingredients' => ['Farine de blé', 'beurre', 'eau', 'sucre', 'levure', 'sel'],
        'allergenes' => ['gluten', 'lait'],
        'nutritionalValues' => [
          'calories' => 230,
          'fat' => 14,
          'carbohydrates' => 22,
          'proteins' => 4
        ],
        'conservation' => 'À consommer le jour de l\'achat, peut être réchauffé.',
        'popularity' => 90
      ],
      [
        'bakery_name' => 'Le Fournil Doré',
        'name' => 'Pain aux Céréales',
        'description' => 'Pain complet aux graines, riche en fibres.',
        'price' => 2.00,
        'category_ref' => 'category-pains',
        'ingredients' => ['Farine complète', 'graines (lin, tournesol, sésame)', 'eau', 'levure', 'sel'],
        'allergenes' => ['gluten'],
        'nutritionalValues' => [
          'calories' => 210,
          'fat' => 3,
          'carbohydrates' => 38,
          'proteins' => 9
        ],
        'conservation' => 'Se conserve plusieurs jours à température ambiante.',
        'popularity' => 75
      ],
      [
        'bakery_name' => 'Le Fournil Doré',
        'name' => 'Éclair au Chocolat',
        'description' => 'Pâtisserie fourrée à la crème pâtissière et nappée de glaçage au chocolat.',
        'price' => 2.20,
        'category_ref' => 'category-patisseries',
        'ingredients' => ['Pâte à choux', 'crème pâtissière', 'chocolat', 'sucre', 'beurre', 'œufs'],
        'allergenes' => ['gluten', 'lait', 'œufs'],
        'nutritionalValues' => [
          'calories' => 280,
          'fat' => 16,
          'carbohydrates' => 29,
          'proteins' => 4
        ],
        'conservation' => 'À conserver au réfrigérateur, consommer rapidement.',
        'popularity' => 88
      ],
      [
        'bakery_name' => 'Le Fournil Doré',
        'name' => 'Quiche Lorraine',
        'description' => 'Tarte salée à base de crème, lardons et fromage.',
        'price' => 3.50,
        'category_ref' => 'category-snacks',
        'ingredients' => ['Pâte brisée', 'œufs', 'crème', 'lardons', 'fromage', 'sel', 'poivre'],
        'allergenes' => ['gluten', 'lait', 'œufs'],
        'nutritionalValues' => [
          'calories' => 350,
          'fat' => 28,
          'carbohydrates' => 8,
          'proteins' => 16
        ],
        'conservation' => 'À consommer rapidement, peut être réchauffée.',
        'popularity' => 70
      ],
      [
        'bakery_name' => 'Le Fournil Doré',
        'name' => 'Pain aux Noix',
        'description' => 'Pain aux noix, idéal pour les amateurs de saveurs rustiques.',
        'price' => 2.50,
        'category_ref' => 'category-pains',
        'ingredients' => ['Farine de blé', 'noix', 'eau', 'levure', 'sel'],
        'allergenes' => ['gluten', 'fruits à coque'],
        'nutritionalValues' => [
          'calories' => 240,
          'fat' => 12,
          'carbohydrates' => 32,
          'proteins' => 8
        ],
        'conservation' => 'Se conserve plusieurs jours à température ambiante.',
        'popularity' => 65
      ],

      // La Baguette Magique
      [
        'bakery_name' => 'La Baguette Magique',
        'name' => 'Pain au Levain',
        'description' => 'Pain à la fermentation lente, saveur unique.',
        'price' => 2.50,
        'category_ref' => 'category-pains',
        'ingredients' => ['Farine de blé', 'eau', 'levain naturel', 'sel'],
        'allergenes' => ['gluten'],
        'nutritionalValues' => [
          'calories' => 220,
          'fat' => 1,
          'carbohydrates' => 45,
          'proteins' => 7
        ],
        'conservation' => 'Conserver dans un sac en tissu, se conserve plusieurs jours.',
        'popularity' => 92
      ],
      [
        'bakery_name' => 'La Baguette Magique',
        'name' => 'Tarte aux Fruits',
        'description' => 'Tarte avec fruits de saison sur pâte sablée.',
        'price' => 3.00,
        'category_ref' => 'category-patisseries',
        'ingredients' => ['Farine de blé', 'beurre', 'sucre', 'œufs', 'fruits de saison', 'confiture'],
        'allergenes' => ['gluten', 'lait', 'œufs'],
        'nutritionalValues' => [
          'calories' => 280,
          'fat' => 12,
          'carbohydrates' => 40,
          'proteins' => 3
        ],
        'conservation' => 'À conserver au réfrigérateur, consommer dans les 2 jours.',
        'popularity' => 80
      ],
      [
        'bakery_name' => 'La Baguette Magique',
        'name' => 'Pain aux Olives',
        'description' => 'Pain parfumé aux olives noires, idéal pour l\'apéritif.',
        'price' => 2.70,
        'category_ref' => 'category-pains',
        'ingredients' => ['Farine de blé', 'olives noires', 'eau', 'levure', 'sel'],
        'allergenes' => ['gluten'],
        'nutritionalValues' => [
          'calories' => 230,
          'fat' => 6,
          'carbohydrates' => 38,
          'proteins' => 7
        ],
        'conservation' => 'Se conserve plusieurs jours à température ambiante.',
        'popularity' => 75
      ],
      [
        'bakery_name' => 'La Baguette Magique',
        'name' => 'Tartelette Citron Meringuée',
        'description' => 'Tartelette au citron avec meringue italienne.',
        'price' => 2.50,
        'category_ref' => 'category-patisseries',
        'ingredients' => ['Pâte sablée', 'crème de citron', 'meringue (blancs d\'œufs, sucre)'],
        'allergenes' => ['gluten', 'œufs'],
        'nutritionalValues' => [
          'calories' => 260,
          'fat' => 8,
          'carbohydrates' => 38,
          'proteins' => 3
        ],
        'conservation' => 'À conserver au réfrigérateur, consommer rapidement.',
        'popularity' => 85
      ],
      [
        'bakery_name' => 'La Baguette Magique',
        'name' => 'Pain de Campagne',
        'description' => 'Pain rustique à la farine de seigle.',
        'price' => 2.30,
        'category_ref' => 'category-pains',
        'ingredients' => ['Farine de seigle', 'farine de blé', 'eau', 'levain', 'sel'],
        'allergenes' => ['gluten'],
        'nutritionalValues' => [
          'calories' => 210,
          'fat' => 2,
          'carbohydrates' => 40,
          'proteins' => 7
        ],
        'conservation' => 'Se conserve plusieurs jours à température ambiante.',
        'popularity' => 70
      ],

      // Le Pain Quotidien
      [
        'bakery_name' => 'Le Pain Quotidien',
        'name' => 'Sandwich Poulet Crudités',
        'description' => 'Sandwich complet avec poulet rôti et légumes.',
        'price' => 4.50,
        'category_ref' => 'category-sandwiches',
        'ingredients' => ['Pain', 'poulet rôti', 'laitue', 'tomates', 'concombre', 'sauce maison'],
        'allergenes' => ['gluten', 'œufs'],
        'nutritionalValues' => [
          'calories' => 400,
          'fat' => 15,
          'carbohydrates' => 40,
          'proteins' => 25
        ],
        'conservation' => 'À consommer le jour de l\'achat, peut être conservé au réfrigérateur.',
        'popularity' => 95
      ],
      [
        'bakery_name' => 'Le Pain Quotidien',
        'name' => 'Muffin Chocolat',
        'description' => 'Muffin moelleux au chocolat noir.',
        'price' => 1.50,
        'category_ref' => 'category-viennoiseries',
        'ingredients' => ['Farine de blé', 'chocolat noir', 'sucre', 'œufs', 'beurre', 'lait'],
        'allergenes' => ['gluten', 'lait', 'œufs'],
        'nutritionalValues' => [
          'calories' => 300,
          'fat' => 15,
          'carbohydrates' => 35,
          'proteins' => 5
        ],
        'conservation' => 'À conserver à température ambiante, consommer dans les 2 jours.',
        'popularity' => 82
      ],
      [
        'bakery_name' => 'Le Pain Quotidien',
        'name' => 'Fougasse',
        'description' => 'Pain plat aromatisé aux herbes de Provence.',
        'price' => 2.80,
        'category_ref' => 'category-pains',
        'ingredients' => ['Farine de blé', 'eau', 'levure', 'huile d\'olive', 'herbes de Provence', 'sel'],
        'allergenes' => ['gluten'],
        'nutritionalValues' => [
          'calories' => 230,
          'fat' => 6,
          'carbohydrates' => 38,
          'proteins' => 7
        ],
        'conservation' => 'Se conserve plusieurs jours à température ambiante.',
        'popularity' => 78
      ],
      [
        'bakery_name' => 'Le Pain Quotidien',
        'name' => 'Pain au Chocolat',
        'description' => 'Petit pain fourré de pépites de chocolat.',
        'price' => 1.30,
        'category_ref' => 'category-viennoiseries',
        'ingredients' => ['Farine de blé', 'pépites de chocolat', 'beurre', 'sucre', 'œufs', 'lait'],
        'allergenes' => ['gluten', 'lait', 'œufs'],
        'nutritionalValues' => [
          'calories' => 280,
          'fat' => 12,
          'carbohydrates' => 36,
          'proteins' => 5
        ],
        'conservation' => 'À conserver à température ambiante, consommer rapidement.',
        'popularity' => 88
      ],
      [
        'bakery_name' => 'Le Pain Quotidien',
        'name' => 'Tarte aux Myrtilles',
        'description' => 'Tarte aux myrtilles sur une pâte sablée.',
        'price' => 3.30,
        'category_ref' => 'category-patisseries',
        'ingredients' => ['Pâte sablée', 'myrtilles', 'sucre', 'beurre', 'crème'],
        'allergenes' => ['gluten', 'lait'],
        'nutritionalValues' => [
          'calories' => 290,
          'fat' => 14,
          'carbohydrates' => 38,
          'proteins' => 3
        ],
        'conservation' => 'À conserver au réfrigérateur, consommer rapidement.',
        'popularity' => 75
      ],
      [
        'bakery_name' => 'Le Pain Quotidien',
        'name' => 'Sandwich Veggie',
        'description' => 'Sandwich aux légumes frais et humus.',
        'price' => 4.20,
        'category_ref' => 'category-sandwiches',
        'ingredients' => ['Pain complet', 'humus', 'concombre', 'tomates', 'avocat', 'roquette'],
        'allergenes' => ['gluten', 'sésame'],
        'nutritionalValues' => [
          'calories' => 380,
          'fat' => 12,
          'carbohydrates' => 42,
          'proteins' => 18
        ],
        'conservation' => 'À consommer le jour de l\'achat.',
        'popularity' => 87
      ]
    ];

    foreach ($products as $productData) {
      $product = new Product();
      $product->setName($productData['name']);
      $product->setSlug(strtolower($this->slugger->slug($productData['name'])->toString()));
      $product->setDescription($productData['description']);
      $product->setPrice($productData['price']);

      if (isset($productData['category_ref']) && $this->hasReference($productData['category_ref'], Category::class)) {
        $category = $this->getReference($productData['category_ref'], Category::class);
        if ($category instanceof Category) {
          $product->setCategory($category);
        }
      }

      $product->setIngredients($productData['ingredients'] ?? []);
      $product->setAllergenes($productData['allergenes'] ?? []);
      $product->setNutritionalValues($productData['nutritionalValues'] ?? []);
      $product->setConservation($productData['conservation'] ?? null);
      $product->setPopularity($productData['popularity'] ?? 0);

      $bakery = $this->findBakeryByName($productData['bakery_name']);
      if ($bakery) {
        $product->setBakery($bakery);
      }

      $manager->persist($product);
    }

    $manager->flush();
  }

  private function findBakeryByName(string $name): ?Bakery {
    foreach ($this->bakeries as $bakery) {
      if ($bakery->getName() === $name) {
        return $bakery;
      }
    }
    return null;
  }

  public function getDependencies(): array {
    return [
      BakeryFixtures::class,
      CategoryFixtures::class,
    ];
  }
}