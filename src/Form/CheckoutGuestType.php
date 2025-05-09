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

class CheckoutGuestType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
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
      ->add('shipping_address', AddressSimpleType::class, [
        'label' => 'Adresse de livraison',
        'required' => true,
      ])
      ->add('different_billing_address', CheckboxType::class, [
        'label' => 'Utiliser une adresse de facturation différente',
        'required' => false,
        'attr' => [
          'class' => 'h-4 w-4 text-[#EDA239] focus:ring-[#EDA239] border-gray-300 rounded'
        ],
      ])
      ->add('billing_address', AddressSimpleType::class, [
        'label' => 'Adresse de facturation',
        'required' => false,
      ])
      ->add('create_account', CheckboxType::class, [
        'label' => 'Créer un compte pour mémoriser mes informations',
        'required' => false,
        'mapped' => false,
        'attr' => [
          'class' => 'h-4 w-4 text-[#EDA239] focus:ring-[#EDA239] border-gray-300 rounded'
        ],
      ])
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

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => null,
    ]);
  }
}