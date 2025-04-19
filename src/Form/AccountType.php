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
        'label' => 'First name',
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter your first name',
          ]),
        ],
      ])
      ->add('lastName', TextType::class, [
        'label' => 'Last name',
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter your last name',
          ]),
        ],
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter your email',
          ]),
          new Email([
            'message' => 'This email is not valid',
          ]),
        ],
      ])
      ->add('phone', TextType::class, [
        'label' => 'Phone',
        'required' => false,
        'constraints' => [
          new Regex([
            'pattern' => '/^\+?[0-9]{10,15}$/',
            'message' => 'This phone number is not valid',
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