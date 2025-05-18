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

/**
 * Formulaire d'inscription utilisateur.
 * Permet aux visiteurs de créer un compte en fournissant leurs informations personnelles,
 * une adresse et en acceptant les conditions d'utilisation.
 */
class RegistrationType extends AbstractType {
  /**
   * Construit le formulaire d'inscription avec tous les champs nécessaires.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      // Informations personnelles de base
      ->add('firstName', TextType::class, [
        'label' => 'Prénom',
        'attr' => [
          'placeholder' => 'Votre prénom',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre prénom',
          ]),
        ],
      ])
      ->add('lastName', TextType::class, [
        'label' => 'Nom',
        'attr' => [
          'placeholder' => 'Votre nom',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre nom',
          ]),
        ],
      ])
      ->add('email', EmailType::class, [
        'label' => 'Email',
        'attr' => [
          'placeholder' => 'Votre email',
        ],
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre email',
          ]),
        ],
      ])
      // Mot de passe avec confirmation
      ->add('plainPassword', RepeatedType::class, [
        'type' => PasswordType::class,
        'mapped' => false,
        'first_options' => [
          'label' => 'Mot de passe',
          'attr' => [
            'placeholder' => 'Mot de passe',
          ],
          'constraints' => [
            new NotBlank([
              'message' => 'Veuillez saisir un mot de passe',
            ]),
            new Length([
              'min' => 8,
              'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
            ])
          ],
        ],
        'second_options' => [
          'label' => 'Confirmer',
          'attr' => [
            'placeholder' => 'Confirmer le mot de passe',
          ],
        ],
        'invalid_message' => 'Les mots de passe ne correspondent pas',
      ])
      // Informations de contact supplémentaires
      ->add('phone', TextType::class, [
        'label' => 'Téléphone',
        'required' => false,
        'attr' => [
          'placeholder' => 'Numéro de téléphone',
        ],
      ])
      // Adresse de l'utilisateur
      ->add('address', AddressType::class, [
        'mapped' => false,
        'required' => false,
        'label' => false
      ])
      // Acceptation des conditions d'utilisation (obligatoire)
      ->add('agreeTerms', CheckboxType::class, [
        'mapped' => false,
        'label' => 'J\'accepte les conditions d\'utilisation et la politique de confidentialité',
        'constraints' => [
          new IsTrue([
            'message' => 'Vous devez accepter nos conditions d\'utilisation.',
          ]),
        ],
      ])
      // Token CSRF commenté
      // ->add('_token', HiddenType::class, [
      // 'mapped' => false,
      // 'error_bubbling' => true,
      // ]);
    ;
  }

  /**
   * Configure les options globales du formulaire.
   * Définit notamment la classe de données associée au formulaire.
   *
   * @param OptionsResolver $resolver Résolveur d'options pour le formulaire
   * @return void
   */
  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([
      'data_class' => User::class,
    ]);
  }
}