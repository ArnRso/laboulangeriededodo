<?php

namespace App\Controller;

use App\Entity\Media;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Sert les fichiers uploadés, qui sont stockés hors de public/ : sans ce
 * passage obligé, une URL devinée contournerait le délai de déblocage.
 */
class MediaFileController extends AbstractController
{
    public function __construct(
        private readonly string $uploadDirectory,
    ) {
    }

    #[Route('/medias/{id}/fichier', name: 'app_media_file', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('view', subject: 'media')]
    public function serve(Media $media): BinaryFileResponse
    {
        $filePath = $media->getFilePath();

        if (null === $filePath) {
            throw $this->createNotFoundException('Ce média n\'a pas de fichier.');
        }

        $absolutePath = $this->uploadDirectory.'/'.$filePath;

        if (!is_file($absolutePath)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $media->getOriginalName() ?? $filePath,
        );

        return $response;
    }
}
