<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Address;
use App\Form\RegistrationType;
use App\Form\ResetPasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\String\ByteString;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_account');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_account');
        }
    
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
          // Hash the password
          $hashedPassword = $passwordHasher->hashPassword(
              $user,
              $form->get('plainPassword')->getData()
          );
          $user->setPassword($hashedPassword);
          
          // Handle address if provided
          $address = $form->get('address')->getData();
          if ($address instanceof Address && $address->getStreet() && $address->getPostalCode() && $address->getCity()) {
              $address->setIsPrimary(true);
              if (!$address->getLabel()) {
                  $address->setLabel('Adresse principale');
              }
              $user->addAddress($address);
          }
          
          $entityManager->persist($user);
          $entityManager->flush();
          
          $this->addFlash('success', 'Votre compte a été créé avec succès ! Vous pouvez maintenant vous connecter.');
          
          return $this->redirectToRoute('app_login');
      }
    
        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request, 
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_account');
        }
        
        $error = null;
        
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $userRepository->findOneBy(['email' => $email]);
            
            if ($user) {
                // Generate a token
                $token = ByteString::fromRandom(32)->toString();
                $expiresAt = new \DateTimeImmutable('+1 hour');
                
                $user->setResetToken($token);
                $user->setResetTokenExpiresAt($expiresAt);
                
                $entityManager->flush();
                
                // Here you would send an email with the reset link
                // For now, we'll just redirect to the check-email page
            }
            
            // We always redirect to check-email, even if the email doesn't exist
            // This prevents user enumeration
            return $this->redirectToRoute('app_check_email');
        }
        
        return $this->render('security/forgot_password.html.twig', [
            'error' => $error
        ]);
    }

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('security/check_email.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function resetPassword(
        string $token,
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $userRepository->findByResetToken($token);
        
        if (!$user) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }
        
        $form = $this->createForm(ResetPasswordType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Reset the token
            $user->setResetToken(null);
            $user->setResetTokenExpiresAt(null);
            
            // Hash the new password
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $form->get('plainPassword')->getData()
            );
            $user->setPassword($hashedPassword);
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.');
            
            return $this->redirectToRoute('app_login');
        }
        
        return $this->render('security/reset_password.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}