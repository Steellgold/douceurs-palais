<?php

namespace App\Controller;

use App\Entity\Address;
use App\Form\AccountType;
use App\Form\AddressType;
use App\Form\ChangePasswordType;
use App\Repository\AddressRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/account')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController {
    #[Route('', name: 'app_account')]
    public function index(): Response {
      return $this->render('account/index.html.twig');
    }

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

    #[Route('/password', name: 'app_account_password')]
    public function changePassword(
      Request $request,
      UserPasswordHasherInterface $passwordHasher,
      EntityManagerInterface $entityManager
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

      return $this->render('account/change_password.html.twig', [
        'form' => $form->createView(),
      ]);
    }

    #[Route('/addresses', name: 'app_account_addresses')]
    public function addresses(): Response {
      return $this->render('account/addresses.html.twig');
    }

    #[Route('/addresses/new', name: 'app_account_address_new')]
    public function newAddress(Request $request, EntityManagerInterface $entityManager): Response {
      $address = new Address();
      $address->setUser($this->getUser());

      $form = $this->createForm(AddressType::class, $address);
      $form->handleRequest($request);

      if ($form->isSubmitted() && $form->isValid()) {
        if ($this->getUser()->getAddresses()->isEmpty()) {
          $address->setIsPrimary(true);
        }

        $entityManager->persist($address);
        $entityManager->flush();

        $this->addFlash('success', 'Votre adresse a été ajoutée avec succès.');

        return $this->redirectToRoute('app_account_addresses');
      }

      return $this->render('account/address_form.html.twig', [
        'form' => $form->createView(),
        'address' => $address,
      ]);
    }

    #[Route('/addresses/{id}/edit', name: 'app_account_address_edit')]
    public function editAddress(
      Address $address,
      Request $request,
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

      return $this->render('account/address_form.html.twig', [
        'form' => $form->createView(),
        'address' => $address,
        'edit' => true,
      ]);
    }

  #[Route('/addresses/{id}/delete', name: 'app_account_address_delete', methods: ['POST'])]
  public function deleteAddress(Address $address, Request $request, EntityManagerInterface $entityManager): Response {
      if ($address->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette adresse.');
      }

      if ($this->isCsrfTokenValid('delete'.$address->getId(), $request->request->get('_token'))) {
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

  #[Route('/addresses/{id}/primary', name: 'app_account_address_primary', methods: ['POST'])]
    public function setPrimaryAddress(
      Address $address, Request $request, AddressRepository $addressRepository
    ): Response{
      if ($address->getUser() !== $this->getUser()) {
        throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette adresse.');
      }

      if ($this->isCsrfTokenValid('primary'.$address->getId(), $request->request->get('_token'))) {
        $addressRepository->setPrimaryAddress($address);
        $this->addFlash('success', 'Votre adresse principale a été modifiée avec succès.');
      }

      return $this->redirectToRoute('app_account_addresses');
    }
}