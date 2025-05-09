<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailService {
  private MailerInterface $mailer;
  private LoggerInterface $logger;
  private string $senderEmail;
  private string $senderName;

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