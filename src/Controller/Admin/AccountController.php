<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Service\PasswordChanger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/mon-compte')]
#[IsGranted('ROLE_ADMIN')]
class AccountController extends AbstractController
{
    #[Route('', name: 'app_admin_account', methods: ['GET', 'POST'])]
    public function index(Request $request, PasswordChanger $passwordChanger): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('newPassword')->getData();

            if (\is_string($currentPassword) && \is_string($newPassword)) {
                $passwordChanger->change($user, $currentPassword, $newPassword);
                $this->addFlash('success', 'Votre mot de passe a été modifié.');

                return $this->redirectToRoute('app_admin_account');
            }
        }

        return $this->render('admin/account/index.html.twig', [
            'form' => $form,
        ]);
    }
}
