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

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController {
  public function __construct(
    private readonly SluggerInterface $slugger
  ) {
  }

  #[Route('', name: 'app_admin')]
  public function index(): Response {
    return $this->render('admin/index.html.twig');
  }

  #[Route('/bakeries', name: 'app_admin_bakeries')]
  public function bakeries(BakeryRepository $bakeryRepository): Response {
    $bakeries = $bakeryRepository->findAll();

    return $this->render('admin/bakeries.html.twig', [
      'bakeries' => $bakeries,
    ]);
  }

  #[Route('/bakeries/new', name: 'app_admin_bakery_new')]
  public function newBakery(Request $request, EntityManagerInterface $entityManager): Response {
    $bakery = new Bakery();
    $form = $this->createForm(BakeryType::class, $bakery);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
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

  #[Route('/bakeries/{id}/edit', name: 'app_admin_bakery_edit')]
  public function editBakery(Bakery $bakery, Request $request, EntityManagerInterface $entityManager): Response {
    $form = $this->createForm(BakeryType::class, $bakery);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
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

  #[Route('/users', name: 'app_admin_users')]
  public function users(UserRepository $userRepository): Response {
    $users = $userRepository->findAll();

    return $this->render('admin/users.html.twig', [
      'users' => $users,
    ]);
  }

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
        $roles = $user->getRoles();
        if (!in_array('ROLE_BAKER', $roles)) {
          $roles[] = 'ROLE_BAKER';
          $user->setRoles($roles);
        }

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

  #[Route('/users/{id}/remove-baker', name: 'app_admin_user_remove_baker')]
  public function removeUserAsBaker(User $user, EntityManagerInterface $entityManager): Response {
    $bakery = $user->getManagedBakery();
    $bakery?->removeBaker($user);

    $roles = array_filter($user->getRoles(), function ($role) {
      return $role !== 'ROLE_BAKER';
    });

    $user->setRoles($roles);
    $entityManager->flush();

    $this->addFlash('success', 'Utilisateur retiré comme boulanger avec succès.');

    return $this->redirectToRoute('app_admin_users');
  }
}