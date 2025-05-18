<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AccountType;
use App\Form\AddressType;
use App\Form\ChangePasswordType;
use App\Repository\AddressRepository;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur gérant l'espace client des utilisateurs connectés
 *
 * Ce contrôleur permet aux utilisateurs connectés de gérer leur compte,
 * consulter leurs commandes, modifier leurs informations personnelles,
 * changer leur mot de passe et gérer leurs adresses.
 */
#[Route('/account')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController {
  /**
   * Affiche la page d'accueil de l'espace client
   *
   * @return Response Page d'accueil de l'espace client
   */
  #[Route('', name: 'app_account')]
  public function index(): Response {
    return $this->render('account/index.html.twig');
  }

  /**
   * Affiche la liste des commandes de l'utilisateur
   *
   * @param OrderRepository $orderRepository Repository des commandes
   * @return Response Page listant les commandes de l'utilisateur
   */
  #[Route('/orders', name: 'app_account_orders')]
  public function orders(OrderRepository $orderRepository): Response {
    $user = $this->getUser();
    $orders = $orderRepository->findByUser($user);

    return $this->render('account/orders.html.twig', [
      'orders' => $orders
    ]);
  }

  /**
   * Affiche et traite le formulaire de modification du profil
   *
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après modification
   */
  #[Route('/profile', name: 'app_account_profile')]
  public function profile(Request $request, EntityManagerInterface $entityManager): Response {
    $user = $this->getUser();
    $form = $this->createForm(AccountType::class, $user);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->flush();

      $this->addFlash('success', 'Vos informations ont été mises à jour avec succès.');

      return $this->redirectToRoute('app_account');
    }

    return $this->render('account/profile.html.twig', [
      'form' => $form->createView(),
    ]);
  }

  /**
   * Affiche et traite le formulaire de changement de mot de passe
   *
   * @param Request $request Requête HTTP
   * @param UserPasswordHasherInterface $passwordHasher Service de hachage des mots de passe
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après modification
   */
  #[Route('/password', name: 'app_account_password')]
  public function changePassword(
    Request                     $request,
    UserPasswordHasherInterface $passwordHasher,
    EntityManagerInterface      $entityManager
  ): Response {
    $user = $this->getUser();
    $form = $this->createForm(ChangePasswordType::class);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      if (!$passwordHasher->isPasswordValid($user, $form->get('currentPassword')->getData())) {
        $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
      } else {
        $hashedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
        $user->setPassword($hashedPassword);

        $entityManager->flush();
        $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');

        return $this->redirectToRoute('app_account');
      }
    }

    return $this->render('account/change-password.html.twig', [
      'form' => $form->createView(),
    ]);
  }

  /**
   * Affiche la liste des adresses de l'utilisateur
   *
   * @return Response Page listant les adresses de l'utilisateur
   */
  #[Route('/addresses', name: 'app_account_addresses')]
  public function addresses(): Response {
    return $this->render('account/addresses.html.twig');
  }

  /**
   * Affiche et traite le formulaire d'ajout d'une nouvelle adresse
   *
   * Si l'utilisateur n'a pas encore d'adresse, la nouvelle adresse sera
   * automatiquement définie comme adresse principale.
   *
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après ajout
   */
  #[Route('/addresses/new', name: 'app_account_address_new')]
  public function newAddress(Request $request, EntityManagerInterface $entityManager): Response {
    $user = $this->getUser();
    $address = new Address();
    $address->setUser($user);

    $form = $this->createForm(AddressType::class, $address);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      if ($user->getAddresses()->isEmpty()) {
        $address->setIsPrimary(true);
      } else {
        $address->setIsPrimary(false);
      }

      $entityManager->persist($address);
      $entityManager->flush();

      $this->addFlash('success', 'Votre adresse a été ajoutée avec succès.');

      return $this->redirectToRoute('app_account_addresses');
    }

    return $this->render('account/address-form.html.twig', [
      'form' => $form->createView(),
      'address' => $address,
    ]);
  }

  /**
   * Affiche et traite le formulaire de modification d'une adresse
   *
   * @param Address $address L'adresse à modifier
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après modification
   * @throws AccessDeniedException Si l'adresse n'appartient pas à l'utilisateur
   */
  #[Route('/addresses/{id}/edit', name: 'app_account_address_edit')]
  public function editAddress(
    Address                $address,
    Request                $request,
    EntityManagerInterface $entityManager
  ): Response {
    if ($address->getUser() !== $this->getUser()) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette adresse.');
    }

    $form = $this->createForm(AddressType::class, $address);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $entityManager->flush();

      $this->addFlash('success', 'Votre adresse a été mise à jour avec succès.');

      return $this->redirectToRoute('app_account_addresses');
    }

    return $this->render('account/address-form.html.twig', [
      'form' => $form->createView(),
      'address' => $address,
      'edit' => true,
    ]);
  }

  /**
   * Traite la suppression d'une adresse
   *
   * Si l'adresse supprimée était l'adresse principale et qu'il reste d'autres adresses,
   * la première adresse restante sera définie comme adresse principale.
   *
   * @param Address $address L'adresse à supprimer
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Redirection après suppression
   * @throws AccessDeniedException Si l'adresse n'appartient pas à l'utilisateur
   */
  #[Route('/addresses/{id}/delete', name: 'app_account_address_delete', methods: ['POST'])]
  public function deleteAddress(Address $address, Request $request, EntityManagerInterface $entityManager): Response {
    if ($address->getUser() !== $this->getUser()) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette adresse.');
    }

    if ($this->isCsrfTokenValid('delete' . $address->getId(), $request->request->get('_token'))) {
      $isPrimary = $address->isIsPrimary();
      $entityManager->remove($address);
      $entityManager->flush();

      if ($isPrimary && !$this->getUser()->getAddresses()->isEmpty()) {
        $newPrimary = $this->getUser()->getAddresses()->first();
        $newPrimary->setIsPrimary(true);
        $entityManager->flush();
      }

      $this->addFlash('success', 'Votre adresse a été supprimée avec succès.');
    }

    return $this->redirectToRoute('app_account_addresses');
  }

  /**
   * Définit une adresse comme adresse principale
   *
   * @param Address $address L'adresse à définir comme principale
   * @param Request $request Requête HTTP
   * @param AddressRepository $addressRepository Repository des adresses
   * @return Response Redirection après modification
   * @throws AccessDeniedException Si l'adresse n'appartient pas à l'utilisateur
   */
  #[Route('/addresses/{id}/primary', name: 'app_account_address_primary', methods: ['POST'])]
  public function setPrimaryAddress(
    Address $address, Request $request, AddressRepository $addressRepository
  ): Response {
    if ($address->getUser() !== $this->getUser()) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette adresse.');
    }

    if ($this->isCsrfTokenValid('primary' . $address->getId(), $request->request->get('_token'))) {
      $addressRepository->setPrimaryAddress($address);
      $this->addFlash('success', 'Votre adresse principale a été modifiée avec succès.');
    }

    return $this->redirectToRoute('app_account_addresses');
  }
}