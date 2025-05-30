<?php

namespace App\Form;

use App\Entity\Ingredient;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de gestion des ingrédients.
 * Permet de créer ou modifier les informations d'un ingrédient,
 * y compris son nom, ses allergènes et son statut végan.
 */
class IngredientType extends AbstractType {
  /**
   * Construit le formulaire d'ingrédient avec tous les champs nécessaires.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('name', TextType::class, [
        'label' => 'Nom de l\'ingrédient',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir un nom pour l\'ingrédient',
          ]),
        ],
        'attr' => [
          'placeholder' => 'Ex: Farine de blé, Lait, Œufs...',
        ],
      ])
      ->add('allergens', CollectionType::class, [
        'label' => 'Allergènes',
        'entry_type' => TextType::class,
        'allow_add' => true,
        'allow_delete' => true,
        'prototype' => true,
        'required' => false,
        'mapped' => true,
        'by_reference' => false,
        'entry_options' => [
          'attr' => [
            'class' => 'allergen-input',
          ],
        ],
      ])
      ->add('isVegan', CheckboxType::class, [
        'label' => 'Cet ingrédient est végan',
        'required' => false,
      ]);
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
      'data_class' => Ingredient::class,
    ]);
  }
}