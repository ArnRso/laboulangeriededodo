<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Envoie les emails d'invitation, avec le lien de définition du mot de passe.
 */
readonly class InvitationMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private string $senderAddress,
        private string $senderName,
        private string $replyToAddress,
    ) {
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendAdminInvitation(User $user, string $token): void
    {
        $this->send(
            $user,
            $token,
            'Votre accès à l\'administration',
            'emails/admin_invitation',
        );
    }

    /**
     * @throws TransportExceptionInterface
     */
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
     *
     * @throws TransportExceptionInterface
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
