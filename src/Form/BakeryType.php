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

class BakeryType extends AbstractType {
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
      ->add('description', TextareaType::class, [
        'label' => 'Description',
        'required' => false,
        'attr' => ['rows' => 3],
      ])
      ->add('story', TextareaType::class, [
        'label' => 'Histoire',
        'required' => false,
        'attr' => ['rows' => 5],
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

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => Bakery::class,
    ]);
  }
}