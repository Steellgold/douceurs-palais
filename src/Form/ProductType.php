<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Ingredient;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

/**
 * Formulaire de gestion des produits.
 * Permet de créer ou modifier les informations d'un produit,
 * y compris ses détails nutritionnels, son prix, et ses ingrédients.
 */
class ProductType extends AbstractType {
  /**
   * Repository des ingrédients pour accéder aux ingrédients de la boulangerie
   */
  private IngredientRepository $ingredientRepository;

  /**
   * Constructeur du formulaire de produit
   *
   * @param IngredientRepository $ingredientRepository Repository des ingrédients
   */
  public function __construct(IngredientRepository $ingredientRepository) {
    $this->ingredientRepository = $ingredientRepository;
  }

  /**
   * Construit le formulaire de produit avec tous les champs nécessaires.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      // Informations de base du produit
      ->add('name', TextType::class, [
        'label' => 'Nom',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir un nom pour le produit',
          ]),
        ],
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'required' => false,
        'attr' => ['rows' => 3],
      ])
      ->add('price', MoneyType::class, [
        'label' => 'Prix',
        'required' => true,
        'currency' => 'EUR',
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir un prix',
          ]),
          new Positive([
            'message' => 'Le prix doit être positif',
          ]),
        ],
      ])
      // Classification du produit
      ->add('category', EntityType::class, [
        'class' => Category::class,
        'choice_label' => 'name',
        'label' => 'Catégorie',
        'required' => false,
        'placeholder' => 'Choisir une catégorie',
      ])
      // Ingrédients du produit (nouvelle relation)
      ->add('productIngredients', EntityType::class, [
        'class' => Ingredient::class,
        'choice_label' => 'name',
        'label' => 'Ingrédients',
        'required' => false,
        'multiple' => true,
        'expanded' => true,
        'query_builder' => function (EntityRepository $er) use ($options) {
          $product = $options['data'] ?? null;
          $bakery = $product ? $product->getBakery() : null;

          if (!$bakery && isset($options['bakery'])) {
            $bakery = $options['bakery'];
          }

          if ($bakery) {
            return $er->createQueryBuilder('i')
              ->where('i.bakery = :bakery')
              ->setParameter('bakery', $bakery)
              ->orderBy('i.name', 'ASC');
          }

          return $er->createQueryBuilder('i')
            ->orderBy('i.name', 'ASC');
        },
      ])
      // Statut végan du produit
      ->add('isVegan', CheckboxType::class, [
        'label' => 'Ce produit est végan',
        'required' => false,
      ])
      // Informations nutritionnelles
      ->add('nutriscore', ChoiceType::class, [
        'label' => 'Nutriscore',
        'required' => true,
        'placeholder' => 'Sélectionner un nutriscore',
        'choices' => [
          'A' => 'A',
          'B' => 'B',
          'C' => 'C',
          'D' => 'D',
          'E' => 'E',
        ],
      ])
      ->add('conservation', TextareaType::class, [
        'label' => 'Conservation',
        'required' => false,
        'attr' => ['rows' => 2],
      ])
      // Images du produit (R2)
      ->add('imageFiles', FileType::class, [
        'label' => 'Images du produit (max 3)',
        'multiple' => true,
        'mapped' => false,
        'required' => false,
        'attr' => [
          'accept' => 'image/jpeg,image/png,image/webp'
        ]
      ])
      // Système de fidélité
      ->add('requiredPoints', IntegerType::class, [
        'label' => 'Points de fidélité requis',
        'required' => false,
        'help' => 'Nombre de points nécessaires pour obtenir ce produit gratuitement. Laisser vide si non disponible avec des points.',
        'attr' => [
          'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-[#EDA239] focus:border-[#EDA239]'
        ]
      ])
      ->add('taxRate', ChoiceType::class, [
        'label' => 'Taux de TVA (%)',
        'required' => true,
        'choices' => [
          '20% (Taux normal)' => 20.0,
          '10% (Taux intermédiaire)' => 10.0,
          '5,5% (Taux réduit)' => 5.5,
          '2,1% (Taux super réduit)' => 2.1,
        ],
        'attr' => [
          'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#EDA239] focus:border-[#EDA239]'
        ]
      ]);

    // Gérer le statut végan automatiquement en fonction des ingrédients sélectionnés
    $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
      $data = $event->getData();
      $form = $event->getForm();

      // Si l'utilisateur a sélectionné des ingrédients
      if (!empty($data['productIngredients'])) {
        // Récupérer les ingrédients sélectionnés
        $ingredientIds = $data['productIngredients'];

        // Vérifier si tous les ingrédients sont végans
        $allVegan = true;
        foreach ($ingredientIds as $id) {
          $ingredient = $this->ingredientRepository->find($id);
          if ($ingredient && !$ingredient->isVegan()) {
            $allVegan = false;
            break;
          }
        }

        // Si l'utilisateur n'a pas explicitement modifié le statut végan, le définir automatiquement
        if (!isset($data['isVegan'])) {
          $data['isVegan'] = $allVegan;
          $event->setData($data);
        }
      }
    });
  }

  /**
   * Configure les options globales du formulaire.
   * Définit notamment la classe de données associée au formulaire.
   *
   * @param OptionsResolver $resolver Résolveur d'options pour le formulaire
   * @return void
   */
  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => Product::class,
      'bakery' => null,
    ]);

    $resolver->setAllowedTypes('bakery', ['null', 'App\Entity\Bakery']);
  }
}