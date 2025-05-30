<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Form\IngredientType;
use App\Repository\IngredientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la gestion des ingrédients
 *
 * Ce contrôleur permet aux boulangers de gérer leur bibliothèque d'ingrédients :
 * - Liste des ingrédients
 * - Ajout, modification, suppression d'ingrédients
 * - Recherche d'ingrédients pour l'auto-complétion
 */
#[Route('/baker/ingredients')]
#[IsGranted('ROLE_BAKER')]
class IngredientController extends AbstractController {
  /**
   * Constructeur du contrôleur d'ingrédients
   *
   * @param IngredientRepository $ingredientRepository Repository des ingrédients
   * @param EntityManagerInterface $entityManager Gestionnaire d'entités Doctrine
   */
  public function __construct(
    private readonly IngredientRepository   $ingredientRepository,
    private readonly EntityManagerInterface $entityManager
  ) {
  }

  /**
   * Affiche la liste des ingrédients de la boulangerie
   *
   * @return Response Page listant les ingrédients
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('', name: 'app_baker_ingredients')]
  public function index(): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    $ingredients = $this->ingredientRepository->findByBakery($bakery);

    return $this->render('baker/ingredients/index.html.twig', [
      'bakery' => $bakery,
      'ingredients' => $ingredients,
    ]);
  }

  /**
   * Affiche et traite le formulaire d'ajout d'un nouvel ingrédient
   *
   * @param Request $request Requête HTTP
   * @return Response Formulaire ou redirection après ajout
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('/new', name: 'app_baker_ingredient_new')]
  public function new(Request $request): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    $ingredient = new Ingredient();
    $ingredient->setBakery($bakery);

    $form = $this->createForm(IngredientType::class, $ingredient);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Vérifier si le nom existe déjà
      if ($this->ingredientRepository->nameExistsForBakery($ingredient->getName(), $bakery)) {
        $this->addFlash('error', 'Un ingrédient avec ce nom existe déjà dans votre bibliothèque.');
      } else {
        $this->entityManager->persist($ingredient);
        $this->entityManager->flush();

        $this->addFlash('success', 'Ingrédient ajouté avec succès.');
        return $this->redirectToRoute('app_baker_ingredients');
      }
    }

    return $this->render('baker/ingredients/new.html.twig', [
      'form' => $form->createView(),
      'bakery' => $bakery,
    ]);
  }

  /**
   * Affiche et traite le formulaire de modification d'un ingrédient
   *
   * @param Ingredient $ingredient L'ingrédient à modifier
   * @param Request $request Requête HTTP
   * @return Response Formulaire ou redirection après modification
   * @throws AccessDeniedException Si l'ingrédient n'appartient pas à la boulangerie de l'utilisateur
   */
  #[Route('/{id}/edit', name: 'app_baker_ingredient_edit')]
  public function edit(Ingredient $ingredient, Request $request): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery || $ingredient->getBakery() !== $bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet ingrédient.');
    }

    $form = $this->createForm(IngredientType::class, $ingredient);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      // Vérifier si le nom existe déjà (sauf pour cet ingrédient)
      if ($this->ingredientRepository->nameExistsForBakery($ingredient->getName(), $bakery, $ingredient->getId())) {
        $this->addFlash('error', 'Un ingrédient avec ce nom existe déjà dans votre bibliothèque.');
      } else {
        $this->entityManager->flush();
        $this->addFlash('success', 'Ingrédient mis à jour avec succès.');
        return $this->redirectToRoute('app_baker_ingredients');
      }
    }

    return $this->render('baker/ingredients/edit.html.twig', [
      'form' => $form->createView(),
      'ingredient' => $ingredient,
      'bakery' => $bakery,
    ]);
  }

  /**
   * Traite la suppression d'un ingrédient
   *
   * @param Ingredient $ingredient L'ingrédient à supprimer
   * @param Request $request Requête HTTP
   * @return Response Redirection après suppression
   * @throws AccessDeniedException Si l'ingrédient n'appartient pas à la boulangerie de l'utilisateur
   */
  #[Route('/{id}/delete', name: 'app_baker_ingredient_delete', methods: ['POST'])]
  public function delete(Ingredient $ingredient, Request $request): Response {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery || $ingredient->getBakery() !== $bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cet ingrédient.');
    }

    if ($this->isCsrfTokenValid('delete' . $ingredient->getId(), $request->request->get('_token'))) {
      // Vérifier si l'ingrédient est utilisé dans des produits
      if (!$ingredient->getProducts()->isEmpty()) {
        $this->addFlash('error', 'Cet ingrédient est utilisé dans un ou plusieurs produits et ne peut pas être supprimé.');
      } else {
        $this->entityManager->remove($ingredient);
        $this->entityManager->flush();
        $this->addFlash('success', 'Ingrédient supprimé avec succès.');
      }
    }

    return $this->redirectToRoute('app_baker_ingredients');
  }

  /**
   * API pour rechercher des ingrédients par nom
   *
   * Utilisé pour l'auto-complétion dans les formulaires de produits.
   *
   * @param Request $request Requête HTTP contenant le terme de recherche
   * @return JsonResponse Liste des ingrédients correspondants au format JSON
   * @throws AccessDeniedException Si l'utilisateur n'est pas associé à une boulangerie
   */
  #[Route('/api/search', name: 'app_baker_ingredient_search', methods: ['GET'])]
  public function search(Request $request): JsonResponse {
    $user = $this->getUser();
    $bakery = $user->getManagedBakery();

    if (!$bakery) {
      throw $this->createAccessDeniedException('Vous n\'êtes pas associé à une boulangerie.');
    }

    $term = $request->query->get('term', '');

    if (strlen($term) < 2) {
      return $this->json([]);
    }

    $ingredients = $this->ingredientRepository->searchByNameAndBakery($bakery, $term);

    $results = [];
    foreach ($ingredients as $ingredient) {
      $results[] = [
        'id' => $ingredient->getId(),
        'name' => $ingredient->getName(),
        'allergens' => $ingredient->getAllergens(),
        'isVegan' => $ingredient->isVegan(),
      ];
    }

    return $this->json($results);
  }
}