<?php

namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductFixtures extends Fixture {

  private SluggerInterface $slugger;

  public function __construct(SluggerInterface $slugger) {
    $this->slugger = $slugger;
  }

  public function load(ObjectManager $manager): void {
    // Produit 1 : La Mie Dorée
    $product1 = new Product();
    $product1->setName('La Mie Dorée')
      ->setSlug('mie-doree')
      ->setDescription('Pain artisanal à croûte dorée et craquante. Mie aérée et moelleuse aux notes de céréales. Fermentation lente pour un goût authentique. Parfait pour tous vos repas.')
      ->setPrice(2.50)
      ->setImages([
        'https://cdn.douceurs-palais.fr/products/f9878fc8-a103-49b4-9e2b-1ef3f1757b98.webp',
        'https://cdn.douceurs-palais.fr/products/543f933b-3913-4680-8221-6874d907f406.webp',
        'https://cdn.douceurs-palais.fr/products/38c22ef4-bb89-442e-9893-b4f2c69ceb63.webp'
      ])
      ->setIngredients([
        'Farine de blé',
        'Eau',
        'Levain naturel',
        'Sel'
      ])
      ->setAllergenes([
        'Gluten'
      ])
      ->setNutriscore('A')
      ->setNutritionalValues([
        'calories' => 220,
        'fat' => 1,
        'carbohydrates' => 45,
        'proteins' => 7
      ])
      ->setConservation('Conserver dans un endroit frais et sec, à l\'abri de la lumière. Se consomme de préférence dans les 3 jours suivant l\'achat.')
      ->setPairings([
        'Fromage de chèvre',
        'Confiture de figues',
        'Jambon cru',
        'Soupe de légumes'
      ]);
    
    $manager->persist($product1);    
    $manager->flush();
  }
}