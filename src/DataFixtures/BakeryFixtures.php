<?php

namespace App\DataFixtures;

use App\Entity\Bakery;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface;

class BakeryFixtures extends Fixture {
  private SluggerInterface $slugger;

  public function __construct(SluggerInterface $slugger) {
    $this->slugger = $slugger;
  }

  public function load(ObjectManager $manager): void {
    $bakeries = [
      [
        'name' => 'Le Fournil Doré',
        'description' => 'Boulangerie artisanale au cœur de Mulhouse, réputée pour ses pains croustillants et viennoiseries maison.',
        'story' => 'Fondée en 1987 par la famille Durand, Le Fournil Doré perpétue la tradition boulangère française avec passion et savoir-faire. Chaque jour, nos artisans boulangers se lèvent aux aurores pour vous proposer des produits frais et savoureux, élaborés selon des recettes transmises de génération en génération.',
        'address' => '12 Rue des Artisans, 68100 Mulhouse',
        'city' => 'Mulhouse',
        'postalCode' => '68100',
        'phone' => '03 89 45 67 89',
        'email' => 'contact@fournildore.fr',
        'website' => 'https://www.fournildore.fr',
        'rating' => 4.8,
        'images' => ['https://cdn.douceurs-palais.fr/bakeries/0873a16d-948d-47c8-9959-dfe9a2e0163b.webp'],
        'openingHours' => [
          'lundi' => '7h00 - 19h00',
          'mardi' => '7h00 - 19h00',
          'mercredi' => '7h00 - 19h00',
          'jeudi' => '7h00 - 19h00',
          'vendredi' => '7h00 - 19h00',
          'samedi' => '7h00 - 19h30',
          'dimanche' => '7h00 - 13h00'
        ]
      ],
      [
        'name' => 'La Baguette Magique',
        'description' => 'Située à Dornach, cette boulangerie propose des pains frais et pâtisseries authentiques grâce à une fermentation lente.',
        'story' => 'La Baguette Magique est née de la passion de Marie et Pierre Lambert pour le bon pain. Après des années d\'expérience dans les plus grandes boulangeries parisiennes, ils ont décidé de s\'installer à Mulhouse pour partager leur savoir-faire. Leur secret ? Une fermentation lente qui révèle tous les arômes et une sélection rigoureuse des meilleurs ingrédients locaux.',
        'address' => '24 Avenue de la Gare, 68200 Mulhouse',
        'city' => 'Mulhouse',
        'postalCode' => '68200',
        'phone' => '03 89 56 78 90',
        'email' => 'contact@baguette-magique.fr',
        'website' => 'https://www.baguette-magique.fr',
        'rating' => 4.3,
        'images' => ['https://cdn.douceurs-palais.fr/bakeries/6c853937-3167-4a9d-a1ca-ec44b8b155c2.webp'],
        'openingHours' => [
          'lundi' => 'Fermé',
          'mardi' => '6h30 - 19h00',
          'mercredi' => '6h30 - 19h00',
          'jeudi' => '6h30 - 19h00',
          'vendredi' => '6h30 - 19h00',
          'samedi' => '6h30 - 19h00',
          'dimanche' => '7h00 - 12h30'
        ]
      ],
      [
        'name' => 'Le Pain Quotidien',
        'description' => 'À Bourtzwiller, découvrez une variété de pains et snacks frais, préparés quotidiennement avec soin et passion.',
        'story' => 'Le Pain Quotidien a été créé en 2010 par Sophie Martin, une passionnée de boulangerie désireuse de proposer des produits sains et savoureux. Notre équipe s\'efforce de créer chaque jour des pains et viennoiseries qui régalent petits et grands. Nous privilégions les farines locales et les méthodes de fabrication traditionnelles pour garantir une qualité constante.',
        'address' => '36 Boulevard des Boulangers, 68200 Mulhouse',
        'city' => 'Mulhouse',
        'postalCode' => '68200',
        'phone' => '03 89 67 89 01',
        'email' => 'contact@pain-quotidien-mulhouse.fr',
        'website' => 'https://www.pain-quotidien-mulhouse.fr',
        'rating' => 4.5,
        'images' => ['https://cdn.douceurs-palais.fr/bakeries/2859a58e-52e8-4d3b-9fcb-dffa2e14f345.webp'],
        'openingHours' => [
          'lundi' => '7h00 - 19h30',
          'mardi' => '7h00 - 19h30',
          'mercredi' => '7h00 - 19h30',
          'jeudi' => '7h00 - 19h30',
          'vendredi' => '7h00 - 19h30',
          'samedi' => '7h00 - 19h30',
          'dimanche' => '7h00 - 13h00'
        ]
      ]
    ];

    foreach ($bakeries as $bakeryData) {
      $bakery = new Bakery();
      $bakery->setName($bakeryData['name']);
      $bakery->setSlug(strtolower($this->slugger->slug($bakeryData['name'])->toString()));
      $bakery->setDescription($bakeryData['description']);
      $bakery->setStory($bakeryData['story']);
      $bakery->setAddress($bakeryData['address']);
      $bakery->setCity($bakeryData['city']);
      $bakery->setPostalCode($bakeryData['postalCode']);
      $bakery->setPhone($bakeryData['phone']);
      $bakery->setEmail($bakeryData['email']);
      $bakery->setWebsite($bakeryData['website']);
      $bakery->setRating($bakeryData['rating']);
      $bakery->setImages($bakeryData['images']);
      $bakery->setOpeningHours($bakeryData['openingHours']);

      $manager->persist($bakery);
    }

    $manager->flush();
  }
}