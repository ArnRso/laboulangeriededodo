<?php

namespace App\Controller;

use App\Entity\Media;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Sert les fichiers uploadés, qui sont stockés hors de public/ : sans ce
 * passage obligé, une URL devinée contournerait le délai de déblocage.
 */
class MediaFileController extends AbstractController
{
    public function __construct(
        private readonly FilesystemOperator $mediaStorage,
    ) {
    }

    #[Route('/medias/{id}/fichier', name: 'app_media_file', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('view', subject: 'media')]
    public function serve(Media $media): StreamedResponse
    {
        $filePath = $media->getFilePath();

        if (null === $filePath) {
            throw $this->createNotFoundException('Ce média n\'a pas de fichier.');
        }

        if (!$this->mediaStorage->fileExists($filePath)) {
            throw $this->createNotFoundException('Fichier introuvable.');
        }

        $response = new StreamedResponse(function () use ($filePath): void {
            $stream = $this->mediaStorage->readStream($filePath);
            fpassthru($stream);
            fclose($stream);
        });

        $response->headers->set('Content-Type', $this->mediaStorage->mimeType($filePath));
        $response->headers->set('Content-Length', (string) $this->mediaStorage->fileSize($filePath));
        $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $media->getOriginalName() ?? $filePath,
            basename($filePath),
        ));

        return $response;
    }
}
