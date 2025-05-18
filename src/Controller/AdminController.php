<?php

namespace App\Controller;

use App\Entity\Bakery;
use App\Entity\User;
use App\Form\BakeryType;
use App\Repository\BakeryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Contrôleur d'administration pour la gestion des boulangeries et des utilisateurs
 *
 * Ce contrôleur permet aux administrateurs de :
 * - Gérer les boulangeries (ajouter, modifier)
 * - Gérer les utilisateurs (attribuer des rôles de boulanger)
 * - Associer des boulangers à des boulangeries
 */
#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController {
  /**
   * Constructeur du contrôleur d'administration
   *
   * @param SluggerInterface $slugger Service de génération de slugs
   */
  public function __construct(
    private readonly SluggerInterface $slugger
  ) {
  }

  /**
   * Affiche le tableau de bord d'administration
   *
   * @return Response Page du tableau de bord
   */
  #[Route('', name: 'app_admin')]
  public function index(): Response {
    return $this->render('admin/index.html.twig');
  }

  /**
   * Affiche la liste des boulangeries
   *
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   * @return Response Page listant les boulangeries
   */
  #[Route('/bakeries', name: 'app_admin_bakeries')]
  public function bakeries(BakeryRepository $bakeryRepository): Response {
    $bakeries = $bakeryRepository->findAll();

    return $this->render('admin/bakeries.html.twig', [
      'bakeries' => $bakeries,
    ]);
  }

  /**
   * Affiche et traite le formulaire d'ajout d'une nouvelle boulangerie
   *
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après ajout
   */
  #[Route('/bakeries/new', name: 'app_admin_bakery_new')]
  public function newBakery(Request $request, EntityManagerInterface $entityManager): Response {
    $bakery = new Bakery();
    $form = $this->createForm(BakeryType::class, $bakery);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Génération du slug à partir du nom de la boulangerie
      $bakery->setSlug(strtolower($this->slugger->slug($bakery->getName())->toString()));

      $entityManager->persist($bakery);
      $entityManager->flush();

      $this->addFlash('success', 'Boulangerie créée avec succès.');

      return $this->redirectToRoute('app_admin_bakeries');
    }

    return $this->render('admin/bakery-form.html.twig', [
      'form' => $form->createView(),
    ]);
  }

  /**
   * Affiche et traite le formulaire de modification d'une boulangerie
   *
   * @param Bakery $bakery La boulangerie à modifier
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Formulaire ou redirection après modification
   */
  #[Route('/bakeries/{id}/edit', name: 'app_admin_bakery_edit')]
  public function editBakery(Bakery $bakery, Request $request, EntityManagerInterface $entityManager): Response {
    $form = $this->createForm(BakeryType::class, $bakery);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Mise à jour du slug à partir du nom modifié
      $bakery->setSlug(strtolower($this->slugger->slug($bakery->getName())->toString()));

      $entityManager->flush();

      $this->addFlash('success', 'Boulangerie mise à jour avec succès.');

      return $this->redirectToRoute('app_admin_bakeries');
    }

    return $this->render('admin/bakery-form.html.twig', [
      'form' => $form->createView(),
      'bakery' => $bakery,
      'edit' => true,
    ]);
  }

  /**
   * Affiche la liste des utilisateurs
   *
   * @param UserRepository $userRepository Repository des utilisateurs
   * @return Response Page listant les utilisateurs
   */
  #[Route('/users', name: 'app_admin_users')]
  public function users(UserRepository $userRepository): Response {
    $users = $userRepository->findAll();

    return $this->render('admin/users.html.twig', [
      'users' => $users,
    ]);
  }

  /**
   * Affiche et traite le formulaire pour définir un utilisateur comme boulanger
   *
   * Vérifie si l'utilisateur n'est pas déjà boulanger dans une autre boulangerie,
   * puis présente un formulaire pour choisir une boulangerie à associer.
   *
   * @param User $user L'utilisateur à définir comme boulanger
   * @param Request $request Requête HTTP
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   * @return Response Formulaire ou redirection après association
   */
  #[Route('/users/{id}/baker', name: 'app_admin_user_set_baker')]
  public function setUserAsBaker(User $user, Request $request, EntityManagerInterface $entityManager, BakeryRepository $bakeryRepository): Response {
    if ($user->getManagedBakery()) {
      $this->addFlash('error', 'Cet utilisateur est déjà boulanger dans une autre boulangerie.');
      return $this->redirectToRoute('app_admin_users');
    }

    $bakeries = $bakeryRepository->findAll();

    if ($request->isMethod('POST')) {
      $bakeryId = $request->request->get('bakery');
      $bakery = $bakeryRepository->find($bakeryId);

      if ($bakery) {
        // Ajout du rôle ROLE_BAKER à l'utilisateur s'il ne l'a pas déjà
        $roles = $user->getRoles();
        if (!in_array('ROLE_BAKER', $roles)) {
          $roles[] = 'ROLE_BAKER';
          $user->setRoles($roles);
        }

        // Association de l'utilisateur à la boulangerie
        $bakery->addBaker($user);

        $entityManager->flush();

        $this->addFlash('success', 'Utilisateur défini comme boulanger avec succès.');
      }

      return $this->redirectToRoute('app_admin_users');
    }

    return $this->render('admin/set-baker.html.twig', [
      'user' => $user,
      'bakeries' => $bakeries,
    ]);
  }

  /**
   * Retire le rôle de boulanger à un utilisateur
   *
   * Supprime l'association avec la boulangerie et retire le rôle ROLE_BAKER.
   *
   * @param User $user L'utilisateur à modifier
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   * @return Response Redirection après modification
   */
  #[Route('/users/{id}/remove-baker', name: 'app_admin_user_remove_baker')]
  public function removeUserAsBaker(User $user, EntityManagerInterface $entityManager): Response {
    $bakery = $user->getManagedBakery();
    $bakery?->removeBaker($user);

    // Suppression du rôle ROLE_BAKER de l'utilisateur
    $roles = array_filter($user->getRoles(), function ($role) {
      return $role !== 'ROLE_BAKER';
    });

    $user->setRoles($roles);
    $entityManager->flush();

    $this->addFlash('success', 'Utilisateur retiré comme boulanger avec succès.');

    return $this->redirectToRoute('app_admin_users');
  }
}