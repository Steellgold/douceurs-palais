<?php

namespace App\Form;

use App\Entity\Address;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de paiement pour les utilisateurs non connectés (invités).
 * Permet de recueillir les informations nécessaires pour finaliser une commande
 * sans nécessiter la création préalable d'un compte.
 */
class CheckoutGuestType extends AbstractType {
  /**
   * Construit le formulaire de paiement invité avec tous les champs nécessaires.
   * Inclut les informations personnelles, adresses de livraison/facturation et options.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      // Informations personnelles
      ->add('firstName', TextType::class, [
        'label' => 'Prénom',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre prénom',
          ]),
        ],
      ])
      ->add('lastName', TextType::class, [
        'label' => 'Nom',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre nom',
          ]),
        ],
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre adresse email',
          ]),
          new Email([
            'message' => 'Cet email n\'est pas valide',
          ]),
        ],
      ])
      ->add('phone', TextType::class, [
        'label' => 'Téléphone',
        'required' => false,
      ])
      // Adresse de livraison obligatoire
      ->add('shipping_address', AddressSimpleType::class, [
        'label' => 'Adresse de livraison',
        'required' => true,
      ])
      // Option pour utiliser une adresse de facturation différente
      ->add('different_billing_address', CheckboxType::class, [
        'label' => 'Utiliser une adresse de facturation différente',
        'required' => false,
        'attr' => [
          'class' => 'h-4 w-4 text-[#EDA239] focus:ring-[#EDA239] border-gray-300 rounded'
        ],
      ])
      // Adresse de facturation optionnelle
      ->add('billing_address', AddressSimpleType::class, [
        'label' => 'Adresse de facturation',
        'required' => false,
      ])
      // Option pour créer un compte
      ->add('create_account', CheckboxType::class, [
        'label' => 'Créer un compte pour mémoriser mes informations',
        'required' => false,
        'mapped' => false,
        'attr' => [
          'class' => 'h-4 w-4 text-[#EDA239] focus:ring-[#EDA239] border-gray-300 rounded'
        ],
      ])
      // Acceptation des CGV (obligatoire)
      ->add('terms', CheckboxType::class, [
        'label' => 'J\'accepte les conditions générales de vente',
        'required' => true,
        'mapped' => false,
        'constraints' => [
          new NotBlank([
            'message' => 'Vous devez accepter les conditions générales de vente',
          ]),
        ],
        'attr' => [
          'class' => 'h-4 w-4 text-[#EDA239] focus:ring-[#EDA239] border-gray-300 rounded'
        ],
      ]);
  }

  /**
   * Configure les options globales du formulaire.
   * Ce formulaire n'est pas lié à une entité spécifique.
   *
   * @param OptionsResolver $resolver Résolveur d'options pour le formulaire
   * @return void
   */
  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => null,
    ]);
  }
}