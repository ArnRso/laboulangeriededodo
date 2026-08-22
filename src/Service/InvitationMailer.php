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
        private readonly string $replyToAddress,
    ) {
    }

    public function sendAdminInvitation(User $user, string $token): void
    {
        $this->send(
            $user,
            $token,
            'Votre accès à l\'administration',
            'emails/admin_invitation',
        );
    }

    public function sendRecipientInvitation(User $user, string $token): void
    {
        $this->send(
            $user,
            $token,
            'Une surprise t\'attend',
            'emails/recipient_invitation',
        );
    }

    /**
     * @param string $template préfixe des templates, sans l'extension : les
     *                         variantes .html.twig et .txt.twig sont envoyées
     *                         ensemble, un message sans version texte étant
     *                         pénalisé par les filtres anti-spam
     */
    private function send(User $user, string $token, string $subject, string $template): void
    {
        $email = new TemplatedEmail()
            ->from(sprintf('%s <%s>', $this->senderName, $this->senderAddress))
            ->to($user->getEmail())
            ->replyTo($this->replyToAddress)
            ->subject($subject)
            ->htmlTemplate($template.'.html.twig')
            ->textTemplate($template.'.txt.twig')
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
