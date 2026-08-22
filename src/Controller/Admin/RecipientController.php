<?php

namespace App\Controller\Admin;

use App\Enum\Avatar;
use App\Form\InviteRecipientType;
use App\Service\InvitationService;
use App\Service\RecipientInviter;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/destinataire')]
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
    ): Response {
        $recipient = $recipientInviter->findRecipient();

        $form = $this->createForm(InviteRecipientType::class, [
            'email' => $recipient?->getEmail(),
            'displayName' => $recipient?->getDisplayName(),
            'avatar' => $recipient?->getAvatar(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();
            $displayName = $form->get('displayName')->getData();
            $avatar = $form->get('avatar')->getData();

            if (\is_string($email) && \is_string($displayName) && $avatar instanceof Avatar) {
                try {
                    $recipientInviter->invite($email, $displayName, $avatar);
                    $this->addFlash('success', 'L\'invitation a été envoyée.');
                } catch (\LogicException|\InvalidArgumentException $exception) {
                    $this->addFlash('error', $exception->getMessage());
                }

                return $this->redirectToRoute('app_admin_recipient');
            }
        }

        return $this->render('admin/recipient/index.html.twig', [
            'form' => $form,
            'recipient' => $recipient,
            'hasPendingInvitation' => null !== $recipient && $invitationService->hasPendingInvitation($recipient),
        ]);
    }
}
