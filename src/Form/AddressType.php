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

class AddressType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('label', TextType::class, [
        'label' => 'Label',
        'required' => false,
        'attr' => [
          'placeholder' => 'Ex: Home, Office, etc.',
        ],
      ])
      ->add('street', TextType::class, [
        'label' => 'Address',
        'attr' => [
          'placeholder' => 'Street number and name',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter an address',
          ]),
        ],
      ])
      ->add('complement', TextType::class, [
        'label' => 'Additional address details',
        'required' => false,
        'attr' => [
          'placeholder' => 'Apartment, floor, etc.',
        ],
      ])
      ->add('postalCode', TextType::class, [
        'label' => 'Postal code',
        'attr' => [
          'placeholder' => 'Postal code',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter a postal code',
          ]),
          new Regex([
            'pattern' => '/^[0-9]{5}$/',
            'message' => 'This postal code is not valid',
          ]),
        ],
      ])
      ->add('city', TextType::class, [
        'label' => 'City',
        'attr' => [
          'placeholder' => 'City',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter a city',
          ]),
        ],
      ])
      ->add('isPrimary', CheckboxType::class, [
        'label' => 'Set as primary address',
        'required' => false,
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => Address::class,
    ]);
  }
}