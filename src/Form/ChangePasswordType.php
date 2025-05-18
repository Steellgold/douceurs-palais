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

/**
 * Formulaire de changement de mot de passe.
 * Permet à l'utilisateur de modifier son mot de passe en fournissant
 * son mot de passe actuel et un nouveau mot de passe (avec confirmation).
 */
class ChangePasswordType extends AbstractType {
  /**
   * Construit le formulaire de changement de mot de passe avec les champs nécessaires.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
      ->add('currentPassword', PasswordType::class, [
        'label' => 'Mot de passe actuel',
        'mapped' => false,
        'constraints' => [
          new NotBlank([
            'message' => 'Veuillez saisir votre mot de passe actuel',
          ]),
        ],
      ])
      ->add('plainPassword', RepeatedType::class, [
        'type' => PasswordType::class,
        'mapped' => false,
        'first_options' => [
          'label' => 'Nouveau mot de passe',
          'constraints' => [
            new NotBlank([
              'message' => 'Veuillez saisir un nouveau mot de passe',
            ]),
            new Length([
              'min' => 8,
              'minMessage' => 'Votre mot de passe doit contenir au moins {{ limit }} caractères',
            ]),
            new Regex([
              'pattern' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
              'message' => 'Votre mot de passe doit contenir au moins une lettre majuscule, une lettre minuscule, un chiffre et un caractère spécial',
            ]),
          ],
        ],
        'second_options' => [
          'label' => 'Confirmer le nouveau mot de passe',
        ],
        'invalid_message' => 'Les mots de passe ne correspondent pas',
      ]);
  }

  /**
   * Configure les options globales du formulaire.
   * Ce formulaire n'est pas lié à une entité spécifique.
   *
   * @param OptionsResolver $resolver Résolveur d'options pour le formulaire
   * @return void
   */
  public function configureOptions(OptionsResolver $resolver): void {
    $resolver->setDefaults([]);
  }
}