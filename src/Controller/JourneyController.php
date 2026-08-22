<?php

namespace App\Controller;

use App\Entity\Media;
use App\Entity\Pack;
use App\Entity\User;
use App\Repository\MediaAccessRepository;
use App\Security\Voter\PackVoter;
use App\Service\ProgressionService;
use App\Service\UnlockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Le parcours du destinataire : choisir un pack, puis en ouvrir les médias
 * un par un au rythme fixé par l'administration.
 */
#[Route('/mon-espace')]
#[IsGranted('ROLE_USER')]
class JourneyController extends AbstractController
{
    #[Route('', name: 'app_journey', methods: ['GET'])]
    public function index(ProgressionService $progressionService, UnlockService $unlockService): Response
    {
        $user = $this->getAppUser();
        $progress = $progressionService->getActiveProgress($user);

        if (null === $progress) {
            return $this->render('journey/choose.html.twig', [
                'packs' => $progressionService->getSelectablePacks($user),
                'completed' => $progressionService->getCompletedProgresses($user),
            ]);
        }

        return $this->render('journey/pack.html.twig', [
            'progress' => $progress,
            'states' => $unlockService->getPackState($user, $progress),
            'nextAvailableAt' => $unlockService->getNextAvailabilityDate($progress),
            'completed' => $progressionService->getCompletedProgresses($user),
        ]);
    }

    #[Route('/packs/{id}/commencer', name: 'app_journey_start', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function start(Request $request, Pack $pack, ProgressionService $progressionService): Response
    {
        if (!$this->isCsrfTokenValid('start_pack_'.$pack->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        try {
            $progressionService->startPack($this->getAppUser(), $pack);
        } catch (\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_journey');
    }

    #[Route('/medias/{id}', name: 'app_journey_media', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function openMedia(Media $media, ProgressionService $progressionService): Response
    {
        $user = $this->getAppUser();
        $progress = $progressionService->getActiveProgress($user);

        if (null === $progress || $progress->getPack() !== $media->getPack()) {
            throw $this->createAccessDeniedException('Ce média ne fait pas partie de votre pack en cours.');
        }

        try {
            $progressionService->openMedia($user, $progress, $media);
        } catch (\LogicException) {
            $this->addFlash('error', 'Ce souvenir n\'est pas encore accessible.');

            return $this->redirectToRoute('app_journey');
        }

        return $this->render('journey/media.html.twig', [
            'media' => $media,
            'progress' => $progress,
        ]);
    }

    #[Route('/packs/{id}', name: 'app_journey_pack_history', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted(PackVoter::VIEW, subject: 'pack')]
    public function history(
        Pack $pack,
        MediaAccessRepository $mediaAccessRepository,
    ): Response {
        // Seuls les médias déjà ouverts sont listés : un pack revisité ne doit
        // pas révéler ce qui reste à découvrir.
        $accesses = $mediaAccessRepository->findForUserAndPack($this->getAppUser(), $pack);

        return $this->render('journey/history.html.twig', [
            'pack' => $pack,
            'accesses' => $accesses,
        ]);
    }

    private function getAppUser(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
