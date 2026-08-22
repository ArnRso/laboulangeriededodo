<?php

namespace App\Controller\Admin;

use App\Entity\Pack;
use App\Form\PackType;
use App\Repository\MediaRepository;
use App\Repository\PackRepository;
use App\Service\PackManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/packs')]
#[IsGranted('ROLE_ADMIN')]
class PackController extends AbstractController
{
    #[Route('', name: 'app_admin_pack_index', methods: ['GET'])]
    public function index(PackRepository $packRepository): Response
    {
        return $this->render('admin/pack/index.html.twig', [
            'packs' => $packRepository->findAllOrdered(),
        ]);
    }

    #[Route('/nouveau', name: 'app_admin_pack_new', methods: ['GET', 'POST'])]
    public function new(Request $request, PackManager $packManager): Response
    {
        $pack = new Pack();
        $form = $this->createForm(PackType::class, $pack);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $packManager->createPack($pack);
            $this->addFlash('success', 'Pack créé.');

            return $this->redirectToRoute('app_admin_pack_show', ['id' => $pack->getId()]);
        }

        return $this->render('admin/pack/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_pack_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Pack $pack, MediaRepository $mediaRepository): Response
    {
        return $this->render('admin/pack/show.html.twig', [
            'pack' => $pack,
            'medias' => $mediaRepository->findByPackOrdered($pack),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_pack_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Pack $pack, PackManager $packManager): Response
    {
        $form = $this->createForm(PackType::class, $pack);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $packManager->updatePack($pack);
            $this->addFlash('success', 'Pack mis à jour.');

            return $this->redirectToRoute('app_admin_pack_show', ['id' => $pack->getId()]);
        }

        return $this->render('admin/pack/edit.html.twig', [
            'form' => $form,
            'pack' => $pack,
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_pack_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Pack $pack, PackManager $packManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_pack_'.$pack->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $packManager->deletePack($pack);
        $this->addFlash('success', 'Pack supprimé.');

        return $this->redirectToRoute('app_admin_pack_index');
    }
}
