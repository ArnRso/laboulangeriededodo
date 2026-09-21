<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Avatar;
use App\Enum\InvitationLifetime;
use App\Form\InviteRecipientType;
use App\Repository\MediaAccessRepository;
use App\Repository\MediaRepository;
use App\Service\InvitationService;
use App\Service\RecipientInviter;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Les destinataires du cadeau : autant qu'on veut, chacun avançant dans le
 * fil à son rythme.
 */
#[Route('/admin/destinataires')]
#[IsGranted('ROLE_ADMIN')]
class RecipientController extends AbstractController
{
    /**
     * Le lien fraîchement généré, le temps d'une redirection.
     */
    private const string INVITATION_LINK = 'invitation_link';

    /**
     * @throws TransportExceptionInterface
     * @throws RandomException
     * @throws \DateMalformedIntervalStringException
     */
    #[Route('', name: 'app_admin_recipient', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        RecipientInviter $recipientInviter,
        InvitationService $invitationService,
        MediaRepository $mediaRepository,
        MediaAccessRepository $mediaAccessRepository,
    ): Response {
        $form = $this->createForm(InviteRecipientType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $displayName = $form->get('displayName')->getData();
            $avatar = $form->get('avatar')->getData();

            $sendEmail = $form->get('sendEmail')->getData();
            $lifetime = $form->get('lifetime')->getData();

            if (\is_string($email) && \is_string($displayName) && $avatar instanceof Avatar) {
                try {
                    if (false === $sendEmail && $lifetime instanceof InvitationLifetime) {
                        [$recipient, $token] = $recipientInviter->inviteWithoutEmail($email, $displayName, $avatar, $lifetime);

                        // Le lien passe par la session plutôt que par un
                        // flash : le gabarit affiche les flash comme des
                        // phrases, et celui-ci porte plusieurs valeurs.
                        $request->getSession()->set(self::INVITATION_LINK, [
                            'name' => $recipient->getPublicName(),
                            'lifetime' => $lifetime->label(),
                            'url' => $this->generateUrl('app_invitation_accept', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
                        ]);
                    } else {
                        $recipient = $recipientInviter->invite($email, $displayName, $avatar);
                        $this->addFlash('success', sprintf('L\'invitation de %s est partie.', $recipient->getPublicName()));
                    }
                } catch (\LogicException|\InvalidArgumentException $exception) {
                    $this->addFlash('error', $exception->getMessage());
                }

                return $this->redirectToRoute('app_admin_recipient');
            }
        }

        $recipients = [];

        foreach ($recipientInviter->findRecipients() as $recipient) {
            $recipients[] = [
                'user' => $recipient,
                'opened' => \count($mediaAccessRepository->findForUser($recipient)),
                'hasPendingInvitation' => $invitationService->hasPendingInvitation($recipient),
            ];
        }

        $session = $request->getSession();
        $invitationLink = $session->get(self::INVITATION_LINK);
        $session->remove(self::INVITATION_LINK);

        return $this->render('admin/recipient/index.html.twig', [
            'form' => $form,
            'recipients' => $recipients,
            'feedLength' => \count($mediaRepository->findPublishedOrdered()),
            'invitationLink' => \is_array($invitationLink) ? $invitationLink : null,
            'lifetimes' => InvitationLifetime::cases(),
        ]);
    }

    /**
     * Renvoie le mail d'invitation à quelqu'un qui n'a pas encore activé son
     * compte.
     *
     * @throws TransportExceptionInterface
     * @throws RandomException
     * @throws \DateMalformedIntervalStringException
     */
    #[Route('/{id}/renvoyer', name: 'app_admin_recipient_resend', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"resend_recipient_" ~ args["recipient"].getId()'))]
    public function resend(User $recipient, RecipientInviter $recipientInviter): Response
    {
        try {
            $recipientInviter->resendEmail($recipient);
            $this->addFlash('success', sprintf('L\'invitation de %s est repartie.', $recipient->getPublicName()));
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_admin_recipient');
    }

    /**
     * Refabrique le lien de quelqu'un, à transmettre à la main.
     *
     * @throws RandomException
     * @throws \DateMalformedIntervalStringException
     */
    #[Route('/{id}/lien', name: 'app_admin_recipient_link', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"link_recipient_" ~ args["recipient"].getId()'))]
    public function link(Request $request, User $recipient, RecipientInviter $recipientInviter): Response
    {
        $lifetime = InvitationLifetime::tryFrom($request->request->getInt('lifetime')) ?? InvitationLifetime::ONE_WEEK;

        try {
            $token = $recipientInviter->refreshLink($recipient, $lifetime);

            $request->getSession()->set(self::INVITATION_LINK, [
                'name' => $recipient->getPublicName(),
                'lifetime' => $lifetime->label(),
                'url' => $this->generateUrl('app_invitation_accept', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_admin_recipient');
    }

    /**
     * Retire quelqu'un du cadeau, définitivement.
     */
    #[Route('/{id}/supprimer', name: 'app_admin_recipient_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete_recipient_" ~ args["recipient"].getId()'))]
    public function delete(User $recipient, RecipientInviter $recipientInviter): Response
    {
        $name = $recipient->getPublicName();

        try {
            $recipientInviter->delete($recipient);
            $this->addFlash('success', sprintf('%s ne fait plus partie du cadeau.', $name));
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_admin_recipient');
    }

    /**
     * Remet quelqu'un au début du fil, pour refaire une démonstration.
     */
    #[Route('/{id}/reinitialiser', name: 'app_admin_recipient_reset', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"reset_recipient_" ~ args["recipient"].getId()'))]
    public function reset(User $recipient, RecipientInviter $recipientInviter): Response
    {
        if ($recipient->isAdmin()) {
            $this->addFlash('error', 'Un administrateur n\'a pas de progression à remettre à zéro.');

            return $this->redirectToRoute('app_admin_recipient');
        }

        $recipientInviter->resetProgress($recipient);
        $this->addFlash('success', sprintf('%s repart du début du fil.', $recipient->getPublicName()));

        return $this->redirectToRoute('app_admin_recipient');
    }
}
