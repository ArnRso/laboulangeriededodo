<?php

namespace App\Controller\Admin;

use App\Entity\Tag;
use App\Form\TagType;
use App\Repository\TagRepository;
use App\Service\TagManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Les étiquettes de rangement des notifications : la liste, l'ajout, le
 * renommage et le retrait.
 */
#[Route('/admin/etiquettes')]
#[IsGranted('ROLE_ADMIN')]
class TagController extends AbstractController
{
    #[Route('', name: 'app_admin_tag_index', methods: ['GET', 'POST'])]
    public function index(Request $request, TagRepository $tagRepository, TagManager $tagManager): Response
    {
        $tag = new Tag();
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($tagManager->nameIsTaken($tag)) {
                $form->get('name')->addError(new FormError('Une étiquette porte déjà ce nom.'));
            } else {
                $tagManager->save($tag);
                $this->addFlash('success', sprintf('L\'étiquette « %s » est prête.', $tag->getName()));

                return $this->redirectToRoute('app_admin_tag_index');
            }
        }

        return $this->render('admin/tag/index.html.twig', [
            'form' => $form,
            'tags' => $tagRepository->findAllOrdered(),
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_tag_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Tag $tag, TagManager $tagManager): Response
    {
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($tagManager->nameIsTaken($tag)) {
                $form->get('name')->addError(new FormError('Une étiquette porte déjà ce nom.'));
            } else {
                $tagManager->save($tag);
                $this->addFlash('success', 'Étiquette renommée.');

                return $this->redirectToRoute('app_admin_tag_index');
            }
        }

        return $this->render('admin/tag/edit.html.twig', [
            'form' => $form,
            'tag' => $tag,
        ]);
    }

    /**
     * Le retrait ne touche pas aux notifications : elles perdent seulement
     * cette étiquette.
     */
    #[Route('/{id}/supprimer', name: 'app_admin_tag_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete_tag_" ~ args["tag"].getId()'))]
    public function delete(Tag $tag, TagManager $tagManager): Response
    {
        $name = $tag->getName();
        $tagManager->delete($tag);
        $this->addFlash('success', sprintf('L\'étiquette « %s » a été retirée.', $name));

        return $this->redirectToRoute('app_admin_tag_index');
    }
}
