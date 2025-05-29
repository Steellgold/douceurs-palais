<?php

namespace App\Controller;

use App\Repository\BakeryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la page d'accueil et la liste des boulangeries
 *
 * Ce contrôleur gère l'affichage de la page d'accueil qui présente
 * soit les produits populaires et les boulangeries populaires (utilisateur non connecté),
 * soit les boulangeries favorites de l'utilisateur (utilisateur connecté).
 */
final class IndexPageController extends AbstractController {
  /**
   * Constructeur du contrôleur de la page d'accueil
   *
   * @param BakeryRepository $bakeryRepository Repository des boulangeries
   * @param ProductRepository $productRepository Repository des produits
   */
  public function __construct(
    private readonly BakeryRepository  $bakeryRepository,
    private readonly ProductRepository $productRepository
  ) {
  }

  /**
   * Affiche la page d'accueil
   *
   * Pour un utilisateur connecté avec des boulangeries favorites,
   * affiche une page personnalisée avec ses favoris.
   * Sinon, affiche les produits et boulangeries populaires.
   *
   * @return Response Page d'accueil personnalisée ou par défaut
   */
  #[Route('/', name: 'app_index_page')]
  public function index(): Response {
    $user = $this->getUser();

    // Récupération des produits et boulangeries populaires (pour tous les utilisateurs)
    $popularProducts = $this->productRepository->findMostPopular();
    $popularBakeries = $this->bakeryRepository->findPopularBakeries(3);

    // Si l'utilisateur est connecté et a des favoris, afficher une page personnalisée
    if ($user) {
      $favoriteCount = $this->bakeryRepository->countFavoritesByUser($user);

      if ($favoriteCount > 0) {
        $favoriteBakeries = $this->bakeryRepository->findFavoritesByUser($user, null);
        $productsFromFavorites = $this->productRepository->findFromFavoriteBakeries($user, 12);

        return $this->render('index_page/favorites.html.twig', [
          'favoriteBakeries' => $favoriteBakeries,
          'products' => $productsFromFavorites
        ]);
      }
    }

    // Affichage par défaut (utilisateur non connecté ou sans favoris)
    return $this->render('index_page/index.html.twig', [
      'popularProducts' => $popularProducts,
      'popularBakeries' => $popularBakeries
    ]);
  }

  /**
   * Affiche la liste de toutes les boulangeries
   *
   * @return Response Page listant toutes les boulangeries
   */
  #[Route('/all', name: 'app_bakery_list')]
  public function list(): Response {
    $bakeries = $this->bakeryRepository->findAll();

    return $this->render('bakery/list.html.twig', [
      'bakeries' => $bakeries,
    ]);
  }


  /**
   * Affiche la page "À propos"
   *
   * @return Response Page "À propos"
   */
  #[Route('/about', name: 'app_about')]
  public function about(): Response {
    return $this->render('pages/about.html.twig');
  }

  /**
   * Affiche la page "FAQ"
   *
   * @return Response Page "FAQ"
   */
  #[Route('/faq', name: 'app_faq')]
  public function faq(): Response {
    return $this->render('pages/faq.html.twig');
  }

  /**
   * Affiche la page de contact
   *
   * @return Response Page de contact
   */
  #[Route('/contact', name: 'app_contact')]
  public function contact(): Response {
    return $this->render('pages/contact.html.twig');
  }

  /**
   * Gère la soumission du formulaire de contact
   *
   * Valide les données du formulaire, envoie un email et affiche un message de succès ou d'erreur.
   *
   * @param Request $request Requête HTTP contenant les données du formulaire
   * @param MailerInterface $mailer Service d'envoi d'emails
   * @param SessionInterface $session Service de session pour stocker les messages flash
   * @return Response Redirige vers la page de contact avec un message de succès ou d'erreur
   */
  #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
  public function contactSubmit(Request $request, MailerInterface $mailer, SessionInterface $session): Response {
    // Récupération des données du formulaire
    $name = $request->request->get('name');
    $email = $request->request->get('email');
    $phone = $request->request->get('phone');
    $subject = $request->request->get('subject');
    $message = $request->request->get('message');
    $privacy = $request->request->get('privacy');

    // Validation des données
    $errors = [];
    if (empty($name)) {
      $errors[] = 'Le nom est obligatoire.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $errors[] = 'L\'email n\'est pas valide.';
    }
    if (empty($subject)) {
      $errors[] = 'Le sujet est obligatoire.';
    }
    if (empty($message)) {
      $errors[] = 'Le message est obligatoire.';
    }
    if (empty($privacy)) {
      $errors[] = 'Vous devez accepter la politique de confidentialité.';
    }

    // Si des erreurs sont détectées, on affiche le formulaire avec les erreurs
    if (count($errors) > 0) {
      foreach ($errors as $error) {
        $this->addFlash('error', $error);
      }
      return $this->redirectToRoute('app_contact');
    }

    // Envoi de l'email
    try {
      $subjectOptions = [
        'order' => 'Question sur une commande',
        'product' => 'Question sur un produit',
        'delivery' => 'Question sur la livraison',
        'account' => 'Question sur mon compte',
        'partnership' => 'Demande de partenariat',
        'other' => 'Autre demande'
      ];

      $subjectText = $subjectOptions[$subject] ?? 'Formulaire de contact';

      $email = (new Email())
        ->from('contact@douceurs-palais.fr')
        ->to('contact@douceurs-palais.fr')
        ->replyTo($email)
        ->subject('Nouveau message : ' . $subjectText)
        ->html(
          $this->renderView('emails/contact.html.twig', [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subjectText,
            'message' => $message
          ])
        );

      $mailer->send($email);

      // Message de succès
      $this->addFlash('success', 'Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.');
    } catch (\Exception $e) {
      // Message d'erreur
      $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer ultérieurement.');
    } catch (TransportExceptionInterface $e) {
      // Gestion des erreurs de transport
      $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer ultérieurement.');
    }

    return $this->redirectToRoute('app_contact');
  }

  /**
   * Affiche la page de livraison
   *
   * @return Response Page de livraison
   */
  #[Route('/delivery', name: 'app_delivery')]
  public function delivery(): Response {
    return $this->render('pages/delivery.html.twig');
  }
}