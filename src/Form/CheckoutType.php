<?php

namespace App\Form;

use App\Entity\Address;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de paiement pour les utilisateurs connectés.
 * Permet de sélectionner les adresses existantes de l'utilisateur pour la livraison
 * et la facturation lors du processus de paiement.
 */
class CheckoutType extends AbstractType {
  /**
   * Construit le formulaire de paiement avec tous les champs nécessaires.
   * Utilise les adresses existantes de l'utilisateur dans des listes déroulantes.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire, dont l'utilisateur
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $user = $options['user'];

    $builder
      // Sélection de l'adresse de livraison parmi les adresses existantes
      ->add('shipping_address', EntityType::class, [
        'class' => Address::class,
        'label' => 'Adresse de livraison',
        'required' => true,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez sélectionner une adresse de livraison',
          ]),
        ],
        'choice_label' => function (Address $address) {
          $label = $address->getLabel() ?: 'Adresse';
          return $label . ' - ' . $address->getStreet() . ', ' . $address->getPostalCode() . ' ' . $address->getCity();
        },
        'choices' => $user->getAddresses(),
        'placeholder' => 'Choisir une adresse de livraison',
        'attr' => [
          'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#EDA239] focus:border-[#EDA239]'
        ],
      ])
      // Option pour utiliser une adresse de facturation différente
      ->add('different_billing_address', CheckboxType::class, [
        'label' => 'Utiliser une adresse de facturation différente',
        'required' => false,
        'attr' => [
          'class' => 'h-4 w-4 text-[#EDA239] focus:ring-[#EDA239] border-gray-300 rounded'
        ],
      ])
      // Sélection de l'adresse de facturation parmi les adresses existantes
      ->add('billing_address', EntityType::class, [
        'class' => Address::class,
        'label' => 'Adresse de facturation',
        'required' => false,
        'choice_label' => function (Address $address) {
          $label = $address->getLabel() ?: 'Adresse';
          return $label . ' - ' . $address->getStreet() . ', ' . $address->getPostalCode() . ' ' . $address->getCity();
        },
        'choices' => $user->getAddresses(),
        'placeholder' => 'Choisir une adresse de facturation',
        'attr' => [
          'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-[#EDA239] focus:border-[#EDA239]'
        ],
      ])
      // Acceptation des CGV (obligatoire)
      ->add('terms', CheckboxType::class, [
        'label' => 'J\'accepte les conditions générales de vente',
        'required' => true,
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
   * Ce formulaire nécessite un utilisateur pour fonctionner.
   *
   * @param OptionsResolver $resolver Résolveur d'options pour le formulaire
   * @return void
   */
  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'user' => null,
    ]);

    // L'option 'user' est obligatoire et doit être une instance de User
    $resolver->setRequired('user');
    $resolver->setAllowedTypes('user', User::class);
  }
}