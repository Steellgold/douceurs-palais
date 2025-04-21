<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class AccountType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('firstName', TextType::class, [
        'label' => 'Prénom',
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre prénom',
          ]),
        ],
      ])
      ->add('lastName', TextType::class, [
        'label' => 'Nom',
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre nom',
          ]),
        ],
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre email',
          ]),
          new Email([
            'message' => 'Cet email n\'est pas valide',
          ]),
        ],
      ])
      ->add('phone', TextType::class, [
        'label' => 'Téléphone',
        'required' => false,
        'constraints' => [
          new Regex([
            'pattern' => '/^\+?[0-9]{10,15}$/',
            'message' => 'Ce numéro de téléphone n\'est pas valide',
          ]),
        ],
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => User::class,
    ]);
  }
}