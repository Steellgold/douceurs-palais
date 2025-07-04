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
 * Formulaire de réinitialisation de mot de passe.
 * Permet à un utilisateur de définir un nouveau mot de passe
 * après avoir initié une procédure de réinitialisation.
 */
class ResetPasswordType extends AbstractType {
  /**
   * Construit le formulaire de réinitialisation de mot de passe.
   *
   * @param FormBuilderInterface $builder Constructeur de formulaire Symfony
   * @param array $options Options supplémentaires pour la configuration du formulaire
   * @return void
   */
  public function buildForm(FormBuilderInterface $builder, array $options): void {
    $builder
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
          'label' => 'Confirmer le mot de passe',
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