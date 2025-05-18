<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

/**
 * Authentificateur pour le formulaire de connexion.
 * Gère le processus d'authentification des utilisateurs via le formulaire de connexion,
 * y compris la création du passeport d'authentification et la redirection après connexion.
 */
class LoginFormAuthenticator extends AbstractLoginFormAuthenticator {
  use TargetPathTrait;

  /**
   * Constante définissant le nom de la route du formulaire de connexion.
   */
  public const LOGIN_ROUTE = 'app_login';

  /**
   * Constructeur de l'authentificateur.
   *
   * @param UrlGeneratorInterface $urlGenerator Générateur d'URL pour les redirections
   */
  public function __construct(
    private readonly UrlGeneratorInterface $urlGenerator
  ) {}

  /**
   * Authentifie l'utilisateur à partir des données du formulaire de connexion.
   * Crée un passeport d'authentification avec les identifiants fournis.
   *
   * @param Request $request Requête HTTP contenant les données du formulaire
   * @return Passport Passeport d'authentification avec les identifiants et badges
   */
  public function authenticate(Request $request): Passport {
    $email = $request->request->get('email', '');

    $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

    return new Passport(
      new UserBadge($email),
      new PasswordCredentials($request->request->get('password', '')),
      [
        new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
        new RememberMeBadge(),
      ]
    );
  }

  /**
   * Gère la redirection après une authentification réussie.
   * Redirige vers la page cible précédemment enregistrée, ou vers la page de compte par défaut.
   *
   * @param Request $request Requête HTTP
   * @param TokenInterface $token Token d'authentification de l'utilisateur
   * @param string $firewallName Nom du pare-feu Symfony utilisé
   * @return Response|null Réponse de redirection
   */
  public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response {
    if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
      return new RedirectResponse($targetPath);
    }

    // Redirection vers la page de compte
    return new RedirectResponse($this->urlGenerator->generate('app_account'));
  }

  /**
   * Retourne l'URL du formulaire de connexion.
   * Utilisée pour rediriger vers la page de connexion en cas d'échec ou de besoin d'authentification.
   *
   * @param Request $request Requête HTTP
   * @return string URL du formulaire de connexion
   */
  protected function getLoginUrl(Request $request): string {
    return $this->urlGenerator->generate(self::LOGIN_ROUTE);
  }
}