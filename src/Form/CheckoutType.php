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

class CheckoutType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $user = $options['user'];

    $builder
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
      ->add('different_billing_address', CheckboxType::class, [
        'label' => 'Utiliser une adresse de facturation différente',
        'required' => false,
        'attr' => [
          'class' => 'h-4 w-4 text-[#EDA239] focus:ring-[#EDA239] border-gray-300 rounded'
        ],
      ])
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

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'user' => null,
    ]);

    $resolver->setRequired('user');
    $resolver->setAllowedTypes('user', User::class);
  }
}