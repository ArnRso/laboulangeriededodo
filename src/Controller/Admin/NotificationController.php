<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Form\AppDetails\AppDetailsRegistry;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use App\Service\FeedManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\EnumRequirement;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Composition du fil de notifications par l'administration.
 */
#[Route('/admin/notifications')]
#[IsGranted('ROLE_ADMIN')]
class NotificationController extends AbstractController
{
    #[Route('', name: 'app_admin_notification_index', methods: ['GET'])]
    public function index(MediaRepository $mediaRepository): Response
    {
        return $this->render('admin/notification/index.html.twig', [
            'medias' => $mediaRepository->findAllOrdered(),
        ]);
    }

    /**
     * Première étape : l'application imitée, qui décide du formulaire.
     */
    #[Route('/nouveau', name: 'app_admin_notification_choose', methods: ['GET'])]
    public function choose(): Response
    {
        return $this->render('admin/notification/choose.html.twig', [
            'appKinds' => AppKind::cases(),
        ]);
    }

    #[Route('/nouveau/{app}', name: 'app_admin_notification_new', requirements: ['app' => new EnumRequirement(AppKind::class)], methods: ['GET', 'POST'])]
    public function new(Request $request, AppKind $app, FeedManager $feedManager, AppDetailsRegistry $registry): Response
    {
        $media = new Media();
        $media->setAppKind($app)->setAppData($registry->defaultsFor($app));

        $form = $this->createForm(MediaType::class, $media, ['app_kind' => $app]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedManager->add($media);
            $this->addFlash('success', 'Notification ajoutée au fil.');

            return $this->redirectToRoute('app_admin_notification_index');
        }

        return $this->render('admin/notification/new.html.twig', [
            'form' => $form,
            'appKind' => $app,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_notification_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Media $media, FeedManager $feedManager): Response
    {
        $form = $this->createForm(MediaType::class, $media, ['app_kind' => $media->getAppKind()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedManager->update($media);
            $this->addFlash('success', 'Notification mise à jour.');

            return $this->redirectToRoute('app_admin_notification_index');
        }

        return $this->render('admin/notification/edit.html.twig', [
            'form' => $form,
            'media' => $media,
        ]);
    }

    /**
     * L'écran d'ouverture tel que le destinataire le verra, sans rien enregistrer.
     */
    #[Route('/{id}/apercu', name: 'app_admin_notification_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function preview(Media $media): Response
    {
        return $this->render($media->getAppKind()->template(), [
            'media' => $media,
            'preview' => true,
            'justOpened' => true,
            'auraTotal' => 1240 + $media->getAuraPoints(),
            'recipient' => null,
        ]);
    }

    #[Route('/{id}/deplacer', name: 'app_admin_notification_move', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"move_media_" ~ args["media"].getId()'))]
    public function move(Request $request, Media $media, FeedManager $feedManager): Response
    {
        $feedManager->move($media, 'up' === $request->request->get('direction') ? -1 : 1);

        return $this->redirectToRoute('app_admin_notification_index');
    }

    #[Route('/{id}/supprimer', name: 'app_admin_notification_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete_media_" ~ args["media"].getId()'))]
    public function delete(Media $media, FeedManager $feedManager): Response
    {
        $feedManager->delete($media);
        $this->addFlash('success', 'Notification supprimée.');

        return $this->redirectToRoute('app_admin_notification_index');
    }
}
