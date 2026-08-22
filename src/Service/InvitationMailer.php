<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Envoie les emails d'invitation, avec le lien de définition du mot de passe.
 */
class InvitationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $senderAddress,
        private readonly string $senderName,
    ) {
    }

    public function sendAdminInvitation(User $user, string $token): void
    {
        $this->send(
            $user,
            $token,
            'Votre accès à l\'administration',
            'emails/admin_invitation.html.twig',
        );
    }

    public function sendRecipientInvitation(User $user, string $token): void
    {
        $this->send(
            $user,
            $token,
            'Une surprise t\'attend',
            'emails/recipient_invitation.html.twig',
        );
    }

    private function send(User $user, string $token, string $subject, string $template): void
    {
        $email = new TemplatedEmail()
            ->from(sprintf('%s <%s>', $this->senderName, $this->senderAddress))
            ->to($user->getEmail())
            ->subject($subject)
            ->htmlTemplate($template)
            ->context([
                'user' => $user,
                'invitationUrl' => $this->urlGenerator->generate(
                    'app_invitation_accept',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
                'expirationDays' => InvitationService::TOKEN_LIFETIME_DAYS,
            ]);

        $this->mailer->send($email);
    }
}
