<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Formulaire d'adresse simplifié.
 * Version allégée du formulaire d'adresse sans certaines fonctionnalités comme l'option "adresse principale".
 * Utilisé principalement pour les adresses temporaires ou dans des contextes simplifiés.
 */
class AddressSimpleType extends AbstractType {
  /**
   * Construit le formulaire d'adresse simplifié avec tous les champs nécessaires.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('label', TextType::class, [
        'label' => 'Libellé',
        'required' => false,
        'attr' => [
          'placeholder' => 'Ex: Domicile, Bureau, etc.',
        ],
      ])
      ->add('street', TextType::class, [
        'label' => 'Adresse',
        'attr' => [
          'placeholder' => 'Numéro et nom de rue',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir une adresse',
          ]),
        ],
      ])
      ->add('complement', TextType::class, [
        'label' => 'Complément d\'adresse',
        'required' => false,
        'attr' => [
          'placeholder' => 'Appartement, étage, etc.',
        ],
      ])
      ->add('postalCode', TextType::class, [
        'label' => 'Code postal',
        'attr' => [
          'placeholder' => 'Code postal',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir un code postal',
          ]),
          new Regex([
            'pattern' => '/^[0-9]{5}$/',
            'message' => 'Ce code postal n\'est pas valide',
          ]),
        ],
      ])
      ->add('city', TextType::class, [
        'label' => 'Ville',
        'attr' => [
          'placeholder' => 'Ville',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir une ville',
          ]),
        ],
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
      'data_class' => Address::class,
    ]);
  }
}