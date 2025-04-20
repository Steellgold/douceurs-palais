<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('firstName', TextType::class, [
        'label' => 'First name',
        'attr' => [
          'placeholder' => 'Your first name',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter your first name',
          ]),
        ],
      ])
      ->add('lastName', TextType::class, [
        'label' => 'Last name',
        'attr' => [
          'placeholder' => 'Your last name',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter your last name',
          ]),
        ],
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'attr' => [
          'placeholder' => 'Your email',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Please enter your email',
          ]),
        ],
      ])
      ->add('plainPassword', RepeatedType::class, [
        'type' => PasswordType::class,
        'mapped' => false,
        'first_options' => [
          'label' => 'Password',
          'attr' => [
            'placeholder' => 'Password',
          ],
          'constraints' => [
            new NotBlank([
              'message' => 'Please enter a password',
            ]),
            new Length([
              'min' => 8,
              'minMessage' => 'Your password must contain at least {{ limit }} characters',
            ])
          ],
        ],
        'second_options' => [
          'label' => 'Confirm',
          'attr' => [
            'placeholder' => 'Confirm password',
          ],
        ],
        'invalid_message' => 'Passwords do not match',
      ])
      ->add('phone', TextType::class, [
        'label' => 'Phone',
        'required' => false,
        'attr' => [
          'placeholder' => 'Phone number',
        ],
      ])
      ->add('address', AddressType::class, [
        'mapped' => false,
        'required' => false,
        'label' => false
      ])
      ->add('agreeTerms', CheckboxType::class, [
        'mapped' => false,
        'label' => 'I agree to the terms of use and privacy policy',
        'constraints' => [
          new IsTrue([
            'message' => 'You must accept our terms of use.',
          ]),
        ],
      ])
      ->add('_token', HiddenType::class, [
          'mapped' => false,
          'error_bubbling' => true,
      ]);
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => User::class,
    ]);
  }
}