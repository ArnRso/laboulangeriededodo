<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Entity\Pack;
use App\Form\MediaType;
use App\Service\PackManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class MediaController extends AbstractController
{
    #[Route('/packs/{id}/medias/nouveau', name: 'app_admin_media_new', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function new(Request $request, Pack $pack, PackManager $packManager): Response
    {
        $media = new Media();
        $form = $this->createForm(MediaType::class, $media);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $packManager->addMedia($pack, $media);
            $this->addFlash('success', 'Média ajouté.');

            return $this->redirectToRoute('app_admin_pack_show', ['id' => $pack->getId()]);
        }

        return $this->render('admin/media/new.html.twig', [
            'form' => $form,
            'pack' => $pack,
        ]);
    }

    #[Route('/medias/{id}/modifier', name: 'app_admin_media_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Media $media, PackManager $packManager): Response
    {
        $form = $this->createForm(MediaType::class, $media);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $packManager->updateMedia();
            $this->addFlash('success', 'Média mis à jour.');

            return $this->redirectToRoute('app_admin_pack_show', ['id' => $media->getPack()->getId()]);
        }

        return $this->render('admin/media/edit.html.twig', [
            'form' => $form,
            'media' => $media,
        ]);
    }

    #[Route('/medias/{id}/deplacer', name: 'app_admin_media_move', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"move_media_" ~ args["media"].getId()'))]
    public function move(Request $request, Media $media, PackManager $packManager): Response
    {
        $packManager->moveMedia($media, 'up' === $request->request->get('direction') ? -1 : 1);

        return $this->redirectToRoute('app_admin_pack_show', ['id' => $media->getPack()->getId()]);
    }

    #[Route('/medias/{id}/supprimer', name: 'app_admin_media_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete_media_" ~ args["media"].getId()'))]
    public function delete(Media $media, PackManager $packManager): Response
    {
        $packId = $media->getPack()->getId();
        $packManager->deleteMedia($media);
        $this->addFlash('success', 'Média supprimé.');

        return $this->redirectToRoute('app_admin_pack_show', ['id' => $packId]);
    }
}
