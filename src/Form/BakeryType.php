<?php

namespace App\Form;

use App\Entity\Bakery;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de gestion des boulangeries.
 * Permet de créer ou modifier les informations d'une boulangerie,
 * y compris ses détails de contact et son adresse.
 */
class BakeryType extends AbstractType {
  /**
   * Construit le formulaire de boulangerie avec tous les champs nécessaires.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('name', TextType::class, [
        'label' => 'Nom',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir un nom pour la boulangerie',
          ]),
        ],
      ])
      ->add('title', TextType::class, [
        'label' => 'Titre',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir un titre pour la description',
          ]),
        ],
      ])
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'required' => false,
        'attr' => ['rows' => 3],
      ])
      ->add('address', TextType::class, [
        'label' => 'Adresse',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir une adresse',
          ]),
        ],
      ])
      ->add('city', TextType::class, [
        'label' => 'Ville',
        'required' => false,
      ])
      ->add('postalCode', TextType::class, [
        'label' => 'Code postal',
        'required' => false,
      ])
      ->add('phone', TextType::class, [
        'label' => 'Téléphone',
        'required' => false,
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'required' => false,
      ])
      ->add('website', TextType::class, [
        'label' => 'Site web',
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
      'data_class' => Bakery::class,
    ]);
  }
}