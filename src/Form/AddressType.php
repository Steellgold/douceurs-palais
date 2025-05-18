<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

/**
 * Formulaire complet d'adresse.
 * Permet de gérer toutes les informations relatives à une adresse utilisateur,
 * incluant l'option pour définir une adresse comme principale.
 */
class AddressType extends AbstractType {
  /**
   * Construit le formulaire d'adresse avec tous les champs nécessaires.
   * Inclut tous les champs du formulaire simple plus l'option "adresse principale".
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
      ])
      ->add('isPrimary', CheckboxType::class, [
        'label' => 'Définir comme adresse principale',
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
      'data_class' => Address::class,
    ]);
  }
}