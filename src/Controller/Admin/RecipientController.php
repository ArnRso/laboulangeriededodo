<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\Avatar;
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

            if (\is_string($email) && \is_string($displayName) && $avatar instanceof Avatar) {
                try {
                    $recipient = $recipientInviter->invite($email, $displayName, $avatar);
                    $this->addFlash('success', sprintf('L\'invitation de %s est partie.', $recipient->getPublicName()));
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

        return $this->render('admin/recipient/index.html.twig', [
            'form' => $form,
            'recipients' => $recipients,
            'feedLength' => \count($mediaRepository->findPublishedOrdered()),
        ]);
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
