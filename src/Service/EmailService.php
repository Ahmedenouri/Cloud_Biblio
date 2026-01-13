<?php
// src/Service/EmailService.php

namespace App\Service;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendVerificationEmail(
        string $recipientEmail,
        string $verificationUrl,
        string $subject
    ): void {
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@bibliotheque.ma', 'Bibliothèque'))
            ->to($recipientEmail)
            ->subject($subject)
            ->htmlTemplate('registration/confirmation_email.html.twig')
            ->context([
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }
}