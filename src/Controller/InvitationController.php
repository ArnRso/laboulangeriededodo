<?php

namespace App\Controller;

use App\Form\DefinePasswordType;
use App\Service\InvitationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class InvitationController extends AbstractController
{
    #[Route('/invitation/{token}', name: 'app_invitation_accept')]
    public function accept(
        string $token,
        Request $request,
        InvitationService $invitationService,
    ): Response {
        $user = $invitationService->findUserByToken($token);

        if (null === $user) {
            return $this->render(
                'invitation/invalid.html.twig',
                response: new Response(status: Response::HTTP_NOT_FOUND),
            );
        }

        $form = $this->createForm(DefinePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if (\is_string($plainPassword)) {
                $invitationService->completeInvitation($user, $plainPassword);
                $this->addFlash('success', 'Votre mot de passe est enregistré, vous pouvez vous connecter.');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('invitation/accept.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }
}
