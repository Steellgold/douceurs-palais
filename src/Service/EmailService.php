<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

/**
 * Service d'envoi d'emails.
 * Permet d'envoyer des emails simples ou basés sur des templates Twig,
 * avec gestion des erreurs et journalisation.
 */
class EmailService {
  /**
   * Service de mailer Symfony.
   */
  private MailerInterface $mailer;

  /**
   * Service de journalisation.
   */
  private LoggerInterface $logger;

  /**
   * Email de l'expéditeur par défaut.
   */
  private string $senderEmail;

  /**
   * Nom de l'expéditeur par défaut.
   */
  private string $senderName;

  /**
   * Constructeur du service d'email.
   *
   * @param MailerInterface $mailer Service d'envoi d'emails Symfony
   * @param LoggerInterface $logger Service de journalisation
   * @param string $senderEmail Email de l'expéditeur par défaut
   * @param string $senderName Nom de l'expéditeur par défaut
   */
  public function __construct(
    MailerInterface $mailer,
    LoggerInterface $logger,
    string          $senderEmail = 'no-reply@douceurs-palais.fr',
    string          $senderName = 'Aux Douceurs du Palais'
  ) {
    $this->mailer = $mailer;
    $this->logger = $logger;
    $this->senderEmail = $senderEmail;
    $this->senderName = $senderName;
  }

  /**
   * Envoie un email simple avec contenu HTML et texte.
   *
   * @param string $to Adresse email du destinataire
   * @param string $subject Sujet de l'email
   * @param string $htmlContent Contenu HTML de l'email
   * @param string $textContent Contenu texte de l'email (version alternative)
   * @param array $attachments Pièces jointes au format [content, name, contentType]
   * @param array $context Contexte supplémentaire pour la journalisation
   * @return bool Succès ou échec de l'envoi
   */
  public function send(
    string $to,
    string $subject,
    string $htmlContent,
    string $textContent,
    array  $attachments = [],
    array  $context = []
  ): bool {
    $email = (new Email())
      ->from(new Address($this->senderEmail, $this->senderName))
      ->to($to)
      ->subject($subject)
      ->html($htmlContent);

    if ($textContent) {
      $email->text($textContent);
    }

    foreach ($attachments as $attachment) {
      $email->attach($attachment['content'], $attachment['name'], $attachment['contentType'] ?? 'application/octet-stream');
    }

    try {
      $this->mailer->send($email);
      $this->logger->info('Email sent successfully', [
        'to' => $to,
        'subject' => $subject,
        'context' => $context
      ]);
      return true;
    } catch (TransportExceptionInterface $e) {
      $this->logger->error('Failed to send email', [
        'to' => $to,
        'subject' => $subject,
        'error' => $e->getMessage(),
        'context' => $context
      ]);
      return false;
    }
  }

  /**
   * Envoie un email basé sur un template Twig.
   *
   * @param string $to Adresse email du destinataire
   * @param string $subject Sujet de l'email
   * @param string $template Chemin vers le template Twig
   * @param array $context Variables à passer au template
   * @param array $attachments Pièces jointes au format [content, name, contentType]
   * @return bool Succès ou échec de l'envoi
   */
  public function sendTemplate(
    string $to,
    string $subject,
    string $template,
    array  $context = [],
    array  $attachments = []
  ): bool {
    $email = (new TemplatedEmail())
      ->from(new Address($this->senderEmail, $this->senderName))
      ->to($to)
      ->subject($subject)
      ->htmlTemplate($template)
      ->context($context);

    foreach ($attachments as $attachment) {
      $email->attach($attachment['content'], $attachment['name'], $attachment['contentType'] ?? 'application/octet-stream');
    }

    try {
      $this->mailer->send($email);
      $this->logger->info('Email sent successfully', [
        'to' => $to,
        'subject' => $subject,
        'template' => $template,
        'context' => $context
      ]);
      return true;
    } catch (TransportExceptionInterface $e) {
      $this->logger->error('Failed to send email', [
        'to' => $to,
        'subject' => $subject,
        'template' => $template,
        'error' => $e->getMessage(),
        'context' => $context
      ]);
      return false;
    }
  }
}