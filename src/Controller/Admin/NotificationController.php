<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Form\AppDetails\AppDetailsRegistry;
use App\Form\DressType;
use App\Form\MediaType;
use App\Repository\MediaRepository;
use App\Repository\TagRepository;
use App\Service\Dressing\AppFieldCatalog;
use App\Service\Dressing\DressMapping;
use App\Service\Dressing\NotificationDresser;
use App\Service\Dressing\SourceCatalog;
use App\Service\FeedManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormInterface;
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
    public function index(Request $request, MediaRepository $mediaRepository, TagRepository $tagRepository): Response
    {
        // Sans filtre, on n'interroge pas la base : « tag » absent vaut zéro,
        // et le repository irait chercher une étiquette qui n'existe pas.
        $tagId = $request->query->getInt('tag');
        $activeTag = 0 !== $tagId ? $tagRepository->find($tagId) : null;
        $medias = $mediaRepository->findAllOrdered();

        if (null !== $activeTag) {
            $medias = array_values(array_filter(
                $medias,
                static fn (Media $media): bool => $media->hasTag($activeTag),
            ));
        }

        return $this->render('admin/notification/index.html.twig', [
            'medias' => $medias,
            'tags' => $tagRepository->findAllOrdered(),
            'activeTag' => $activeTag,
        ]);
    }

    /**
     * Première étape : l'application imitée, qui décide du formulaire.
     */
    #[Route('/nouveau', name: 'app_admin_notification_choose', methods: ['GET'])]
    public function choose(): Response
    {
        return $this->render('admin/notification/choose.html.twig', [
            'appKinds' => AppKind::byCategory(),
            'media' => null,
        ]);
    }

    /**
     * Habiller une notification : d'abord l'application, puis le mapping.
     */
    #[Route('/{id}/habiller', name: 'app_admin_notification_dress_choose', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function dressChoose(Media $media, NotificationDresser $dresser): Response
    {
        if (!$dresser->canDress($media)) {
            return $this->refuseDressing($media);
        }

        return $this->render('admin/notification/choose.html.twig', [
            'appKinds' => AppKind::byCategory(),
            'media' => $media,
        ]);
    }

    #[Route('/{id}/habiller/{app}', name: 'app_admin_notification_dress', requirements: ['id' => '\d+', 'app' => new EnumRequirement(AppKind::class)], methods: ['GET', 'POST'])]
    public function dress(Request $request, Media $media, AppKind $app, AppFieldCatalog $catalog, SourceCatalog $sourceCatalog, NotificationDresser $dresser, FeedManager $feedManager): Response
    {
        if (!$dresser->canDress($media)) {
            return $this->refuseDressing($media);
        }

        $form = $this->dressForm($media, $app, $catalog, $sourceCatalog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dresser->dress($media, $app, DressMapping::fromFormData($form->getData()));
            $feedManager->update($media);
            $this->addFlash('success', sprintf('Habillée en %s. Vérifie les détails avant de la mettre dans le fil.', $app->label()));

            return $this->redirectToRoute('app_admin_notification_edit', ['id' => $media->getId()]);
        }

        return $this->render('admin/notification/dress.html.twig', [
            'form' => $form,
            'media' => $media,
            'appKind' => $app,
            'fields' => $catalog->fieldsFor($app),
            'sources' => $sourceCatalog->for($media),
        ]);
    }

    /**
     * L'écran tel que le mapping en cours le donnerait, sans rien enregistrer.
     */
    #[Route('/{id}/habiller/{app}/apercu', name: 'app_admin_notification_dress_preview', requirements: ['id' => '\d+', 'app' => new EnumRequirement(AppKind::class)], methods: ['POST'])]
    public function dressPreview(Request $request, Media $media, AppKind $app, AppFieldCatalog $catalog, SourceCatalog $sourceCatalog, NotificationDresser $dresser): Response
    {
        $form = $this->dressForm($media, $app, $catalog, $sourceCatalog);
        $form->handleRequest($request);

        $draft = clone $media;
        $dresser->dress($draft, $app, DressMapping::fromFormData($form->getData()));

        return $this->renderScreen($draft, embedded: true);
    }

    /**
     * Une notification sans application : on remplit ce qu'on a, on l'habille
     * plus tard.
     */
    #[Route('/brouillon', name: 'app_admin_notification_draft_new', methods: ['GET', 'POST'])]
    public function draft(Request $request, FeedManager $feedManager): Response
    {
        $media = new Media();
        $media->setPublished(false);

        $form = $this->createForm(MediaType::class, $media, ['app_kind' => null]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedManager->add($media);
            $this->addFlash('success', 'Brouillon enregistré. Habille-le quand tu veux.');

            return $this->redirectToRoute('app_admin_notification_index');
        }

        return $this->render('admin/notification/draft.html.twig', [
            'form' => $form,
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
    public function edit(Request $request, Media $media, FeedManager $feedManager, NotificationDresser $dresser): Response
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
            'canDress' => $dresser->canDress($media),
        ]);
    }

    /**
     * L'écran d'ouverture tel que le destinataire le verra, sans rien enregistrer.
     * En POST, c'est le formulaire en cours de saisie qui est rendu, pour le
     * panneau d'aperçu en direct.
     */
    #[Route('/{id}/apercu', name: 'app_admin_notification_preview', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function preview(Request $request, Media $media): Response
    {
        if ($media->isDraft()) {
            $this->addFlash('info', 'Ce brouillon n\'a pas encore d\'application : choisis-la pour voir son écran.');

            return $this->redirectToRoute('app_admin_notification_dress_choose', ['id' => $media->getId()]);
        }

        if ($request->isMethod('POST')) {
            return $this->renderDraft($request, clone $media);
        }

        return $this->renderScreen($media, embedded: false);
    }

    /**
     * Aperçu en direct d'une notification pas encore créée.
     */
    #[Route('/nouveau/{app}/apercu', name: 'app_admin_notification_draft_preview', requirements: ['app' => new EnumRequirement(AppKind::class)], methods: ['POST'])]
    public function draftPreview(Request $request, AppKind $app, AppDetailsRegistry $registry, MediaRepository $mediaRepository): Response
    {
        $media = new Media();
        $media
            ->setAppKind($app)
            ->setAppData($registry->defaultsFor($app))
            ->setPosition($mediaRepository->findMaxPosition() + 1);

        return $this->renderDraft($request, $media);
    }

    #[Route('/{id}/deplacer', name: 'app_admin_notification_move', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"move_media_" ~ args["media"].getId()'))]
    public function move(Request $request, Media $media, FeedManager $feedManager): Response
    {
        $feedManager->move($media, 'up' === $request->request->get('direction') ? -1 : 1);

        return $this->redirectToRoute('app_admin_notification_index');
    }

    /**
     * Met une notification dans le fil, ou l'en retire.
     */
    #[Route('/{id}/basculer', name: 'app_admin_notification_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"toggle_media_" ~ args["media"].getId()'))]
    public function toggle(Media $media, FeedManager $feedManager): Response
    {
        if ($media->isDraft()) {
            $this->addFlash('error', 'Un brouillon ne peut pas entrer dans le fil : habille-le d\'abord.');

            return $this->redirectToRoute('app_admin_notification_index');
        }

        $media->setPublished(!$media->isPublished());
        $feedManager->update($media);

        return $this->redirectToRoute('app_admin_notification_index');
    }

    /**
     * Le nouvel ordre après un glisser-déposer, envoyé par le navigateur.
     */
    #[Route('/reordonner', name: 'app_admin_notification_reorder', methods: ['POST'])]
    #[IsCsrfTokenValid('reorder_medias')]
    public function reorder(Request $request, FeedManager $feedManager): Response
    {
        $ids = $request->request->all('ids');

        $feedManager->reorder(array_values(array_map(intval(...), array_filter($ids, is_scalar(...)))));

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_notification_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsCsrfTokenValid(new Expression('"delete_media_" ~ args["media"].getId()'))]
    public function delete(Media $media, FeedManager $feedManager): Response
    {
        $feedManager->delete($media);
        $this->addFlash('success', 'Notification supprimée.');

        return $this->redirectToRoute('app_admin_notification_index');
    }

    /**
     * Relie la saisie au média et l'affiche même incomplète : l'aperçu suit
     * la frappe, la validation n'intervient qu'à l'enregistrement.
     */
    private function renderDraft(Request $request, Media $media): Response
    {
        $form = $this->createForm(MediaType::class, $media, ['app_kind' => $media->requireAppKind()]);
        $form->handleRequest($request);

        return $this->renderScreen($media, embedded: true);
    }

    /**
     * @return FormInterface<array<string, mixed>|null>
     */
    private function dressForm(Media $media, AppKind $app, AppFieldCatalog $catalog, SourceCatalog $sourceCatalog): FormInterface
    {
        return $this->createForm(DressType::class, null, [
            'fields' => $catalog->fieldsFor($app),
            'sources' => $sourceCatalog->for($media),
        ]);
    }

    private function refuseDressing(Media $media): Response
    {
        $this->addFlash('error', 'Dorian a déjà ouvert cette notification : elle ne change plus d\'application.');

        return $this->redirectToRoute('app_admin_notification_edit', ['id' => $media->getId()]);
    }

    private function renderScreen(Media $media, bool $embedded): Response
    {
        return $this->render($media->requireAppKind()->template(), [
            'media' => $media,
            'preview' => true,
            'embedded' => $embedded,
            'justOpened' => true,
            'recipient' => null,
        ]);
    }
}
