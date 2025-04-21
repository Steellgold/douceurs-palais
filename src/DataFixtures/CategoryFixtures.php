<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryFixtures extends Fixture {
  private SluggerInterface $slugger;

  public function __construct(SluggerInterface $slugger) {
    $this->slugger = $slugger;
  }

  public function load(ObjectManager $manager): void {
    $categories = [
      [
        'name' => 'Pains',
        'description' => 'Nos pains artisanaux, fabriqués avec passion et savoir-faire.',
        'position' => 1,
        'image' => 'categories/pains.jpg',
        'reference' => 'category-pains'
      ],
      [
        'name' => 'Viennoiseries',
        'description' => 'Des viennoiseries gourmandes pour bien commencer la journée.',
        'position' => 2,
        'image' => 'categories/viennoiseries.jpg',
        'reference' => 'category-viennoiseries'
      ],
      [
        'name' => 'Pâtisseries',
        'description' => 'Des pâtisseries fines et savoureuses pour tous les moments de la journée.',
        'position' => 3,
        'image' => 'categories/patisseries.jpg',
        'reference' => 'category-patisseries'
      ],
      [
        'name' => 'Sandwiches',
        'description' => 'Des sandwiches frais préparés avec des produits de qualité.',
        'position' => 4,
        'image' => 'categories/sandwiches.jpg',
        'reference' => 'category-sandwiches'
      ],
      [
        'name' => 'Snacks',
        'description' => 'Des snacks savoureux pour les petites faims.',
        'position' => 5,
        'image' => 'categories/snacks.jpg',
        'reference' => 'category-snacks'
      ]
    ];

    foreach ($categories as $categoryData) {
      $category = new Category();
      $category->setName($categoryData['name']);
      $category->setSlug(strtolower($this->slugger->slug($categoryData['name'])->toString()));
      $category->setDescription($categoryData['description']);
      $category->setPosition($categoryData['position']);
      $category->setImage($categoryData['image']);

      $manager->persist($category);
      $this->addReference($categoryData['reference'], $category);
    }

    $manager->flush();
  }
}