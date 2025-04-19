<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class ResetPasswordType extends AbstractType {
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('plainPassword', RepeatedType::class, [
        'type' => PasswordType::class,
        'mapped' => false,
        'first_options' => [
          'label' => 'New password',
          'constraints' => [
            new NotBlank([
              'message' => 'Please enter a new password',
            ]),
            new Length([
              'min' => 8,
              'minMessage' => 'Your password must contain at least {{ limit }} characters',
            ]),
            new Regex([
              'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
              'message' => 'Your password must contain at least one uppercase letter, one lowercase letter, one number and one special character',
            ]),
          ],
        ],
        'second_options' => [
          'label' => 'Confirm password',
        ],
        'invalid_message' => 'Passwords do not match',
      ])
    ;
  }

  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([]);
  }
}