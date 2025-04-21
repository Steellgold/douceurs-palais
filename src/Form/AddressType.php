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
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => Address::class,
    ]);
  }
}