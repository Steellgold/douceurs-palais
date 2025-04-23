<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Product;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class ProductType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
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
      ->add('category', EntityType::class, [
        'class' => Category::class,
        'choice_label' => 'name',
        'label' => 'Catégorie',
        'required' => false,
        'placeholder' => 'Choisir une catégorie',
      ])
      ->add('nutriscore', ChoiceType::class, [
        'label' => 'Nutriscore',
        'required' => false,
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
      ]);
  }

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => Product::class,
    ]);
  }
}