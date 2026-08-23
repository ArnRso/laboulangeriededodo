<?php

namespace App\Controller;

use App\Entity\Media;
use App\Entity\User;
use App\Service\AuraService;
use App\Service\FeedService;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Le fil du destinataire : ses notifications, et l'ouverture de chacune.
 */
#[Route('/mon-espace')]
#[IsGranted('ROLE_USER')]
class FeedController extends AbstractController
{
    #[Route('', name: 'app_feed', methods: ['GET'])]
    public function index(FeedService $feedService, AuraService $auraService, ClockInterface $clock): Response
    {
        $user = $this->getRecipient();

        if ($user->isAdmin()) {
            return $this->redirectToRoute('app_admin_notification_index');
        }

        $now = $clock->now();

        return $this->render('feed/index.html.twig', [
            'overview' => $feedService->getOverview($user),
            'auraTotal' => $auraService->total($user),
            'auraToday' => $auraService->today($user),
            'now' => $now,
            'dateLabel' => $this->frenchDate($now),
            'recipient' => $user,
        ]);
    }

    #[Route('/notifications/{id}', name: 'app_feed_open', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function open(Media $media, FeedService $feedService, AuraService $auraService): Response
    {
        $user = $this->getRecipient();

        if ($user->isAdmin()) {
            return $this->redirectToRoute('app_admin_notification_preview', ['id' => $media->getId()]);
        }

        $justOpened = !$feedService->hasOpened($user, $media);

        try {
            $feedService->open($user, $media);
        } catch (\LogicException) {
            $this->addFlash('error', 'Cette notification n\'est pas encore arrivée. Patience.');

            return $this->redirectToRoute('app_feed');
        }

        return $this->render($media->getAppKind()->template(), [
            'media' => $media,
            'preview' => false,
            'justOpened' => $justOpened,
            'auraTotal' => $auraService->total($user),
            'recipient' => $user,
        ]);
    }

    /**
     * Le coup de pouce de démonstration : la prochaine notification arrive
     * tout de suite, sans attendre son délai.
     */
    #[Route('/coup-de-pouce', name: 'app_feed_skip', methods: ['POST'])]
    #[IsCsrfTokenValid('feed_skip')]
    public function skip(FeedService $feedService): Response
    {
        $user = $this->getRecipient();

        if ($user->isAdmin()) {
            return $this->redirectToRoute('app_admin_notification_index');
        }

        try {
            $media = $feedService->skipWait($user);
        } catch (\LogicException) {
            $this->addFlash('error', 'Rien n\'attend son tour : le fil est à jour.');

            return $this->redirectToRoute('app_feed');
        }

        $this->addFlash('success', sprintf('Le temps a sauté. « %s » vient d\'arriver.', $media->getTitle()));

        return $this->redirectToRoute('app_feed');
    }

    private function getRecipient(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    /**
     * « samedi 23 août », sans dépendre de la locale du serveur. Le navigateur
     * reprend la main ensuite avec l'heure et la date du téléphone.
     */
    private function frenchDate(\DateTimeImmutable $date): string
    {
        $days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

        return sprintf(
            '%s %d %s',
            $days[(int) $date->format('w')],
            (int) $date->format('j'),
            $months[(int) $date->format('n') - 1],
        );
    }
}
