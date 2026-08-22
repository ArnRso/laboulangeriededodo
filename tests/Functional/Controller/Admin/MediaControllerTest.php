<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Media;
use App\Enum\MediaType;
use App\Repository\MediaRepository;
use App\Tests\Factory\PackFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MediaControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private PackFactory $packFactory;
    private MediaRepository $mediaRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->packFactory = new PackFactory($entityManager);
        $this->mediaRepository = $container->get(MediaRepository::class);

        $userFactory = new UserFactory($entityManager, $container->get(UserPasswordHasherInterface::class));
        $this->client->loginUser($userFactory->createAdmin());
    }

    public function testAddTextMedia(): void
    {
        $pack = $this->packFactory->createPack();

        $this->client->request('GET', sprintf('/admin/packs/%d/medias/nouveau', (int) $pack->getId()));
        $this->client->submitForm('Ajouter', [
            'media[title]' => 'Le premier jour',
            'media[description]' => 'Là où tout a commencé',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'Tu te souviens ?',
        ]);

        self::assertResponseRedirects();

        $media = $this->mediaRepository->findOneBy(['title' => 'Le premier jour']);
        self::assertNotNull($media);
        self::assertSame(MediaType::TEXT, $media->getType());
        self::assertSame(0, $media->getPosition(), 'Le premier média est en tête.');
    }

    public function testFormOffersOneTabPerMediaType(): void
    {
        $pack = $this->packFactory->createPack();

        $crawler = $this->client->request('GET', sprintf('/admin/packs/%d/medias/nouveau', (int) $pack->getId()));

        self::assertCount(
            5,
            $crawler->filter('[data-media-type-target="tab"]'),
            'Photo, vidéo, audio, texte et lien.',
        );
        self::assertSelectorExists('[data-controller="media-type"]');
    }

    public function testUploadedFileMustMatchTheChosenType(): void
    {
        $pack = $this->packFactory->createPack();

        $this->client->request('GET', sprintf('/admin/packs/%d/medias/nouveau', (int) $pack->getId()));
        $this->client->submitForm('Ajouter', [
            'media[title]' => 'Fichier incohérent',
            'media[type]' => MediaType::VIDEO->value,
            'media[file]' => $this->createUploadedImage(),
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNull(
            $this->mediaRepository->findOneBy(['title' => 'Fichier incohérent']),
            'Une image déposée comme vidéo est refusée.',
        );
    }

    public function testAddLinkMedia(): void
    {
        $pack = $this->packFactory->createPack();

        $this->client->request('GET', sprintf('/admin/packs/%d/medias/nouveau', (int) $pack->getId()));
        $this->client->submitForm('Ajouter', [
            'media[title]' => 'La chanson',
            'media[type]' => MediaType::LINK->value,
            'media[url]' => 'https://open.spotify.com/track/123',
        ]);

        self::assertResponseRedirects();

        $media = $this->mediaRepository->findOneBy(['title' => 'La chanson']);
        self::assertNotNull($media);
        self::assertSame('https://open.spotify.com/track/123', $media->getUrl());
    }

    public function testLinkMediaWithoutUrlIsRejected(): void
    {
        $pack = $this->packFactory->createPack();

        $this->client->request('GET', sprintf('/admin/packs/%d/medias/nouveau', (int) $pack->getId()));
        $this->client->submitForm('Ajouter', [
            'media[title]' => 'Sans lien',
            'media[type]' => MediaType::LINK->value,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNull($this->mediaRepository->findOneBy(['title' => 'Sans lien']));
    }

    public function testTextMediaWithoutContentIsRejected(): void
    {
        $pack = $this->packFactory->createPack();

        $this->client->request('GET', sprintf('/admin/packs/%d/medias/nouveau', (int) $pack->getId()));
        $this->client->submitForm('Ajouter', [
            'media[title]' => 'Sans contenu',
            'media[type]' => MediaType::TEXT->value,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadImageMedia(): void
    {
        $pack = $this->packFactory->createPack();

        $this->client->request('GET', sprintf('/admin/packs/%d/medias/nouveau', (int) $pack->getId()));
        $this->client->submitForm('Ajouter', [
            'media[title]' => 'Une photo',
            'media[type]' => MediaType::IMAGE->value,
            'media[file]' => $this->createUploadedImage(),
        ]);

        self::assertResponseRedirects();

        $media = $this->mediaRepository->findOneBy(['title' => 'Une photo']);
        self::assertNotNull($media);
        self::assertNotNull($media->getFilePath(), 'Le fichier doit être enregistré.');
        self::assertSame('souvenir.png', $media->getOriginalName());

        $this->removeUploadedFile($media);
    }

    public function testUploadedFileIsStoredOutsidePublicDirectory(): void
    {
        $pack = $this->packFactory->createPack();

        $this->client->request('GET', sprintf('/admin/packs/%d/medias/nouveau', (int) $pack->getId()));
        $this->client->submitForm('Ajouter', [
            'media[title]' => 'Une photo',
            'media[type]' => MediaType::IMAGE->value,
            'media[file]' => $this->createUploadedImage(),
        ]);

        $media = $this->mediaRepository->findOneBy(['title' => 'Une photo']);
        self::assertNotNull($media);

        $publicPath = self::getContainer()->getParameter('kernel.project_dir').'/public/'.$media->getFilePath();
        self::assertFileDoesNotExist(
            $publicPath,
            'Un fichier dans public/ serait accessible sans passer par le contrôle d\'accès.',
        );

        $this->removeUploadedFile($media);
    }

    public function testMoveMediaDown(): void
    {
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);

        $crawler = $this->client->request('GET', sprintf('/admin/packs/%d', (int) $pack->getId()));
        $form = $crawler->filter('.list-group-item')->eq(0)->filter('form')->eq(1)->form();
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame(['Média 2', 'Média 1', 'Média 3'], $this->titlesInOrder($pack));
    }

    public function testMoveMediaRequiresValidCsrfToken(): void
    {
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 2);

        $this->client->request('POST', sprintf('/admin/medias/%d/deplacer', (int) $medias[0]->getId()), [
            '_token' => 'jeton-invalide',
            'direction' => 'down',
        ]);

        // Un jeton invalide invalide la session : le firewall renvoie vers la
        // connexion plutôt que de servir un 403.
        self::assertResponseRedirects();
        self::assertSame(['Média 1', 'Média 2'], $this->titlesInOrder($pack), 'L\'ordre est inchangé.');
    }

    public function testDeleteMediaClosesTheGap(): void
    {
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);

        $crawler = $this->client->request('GET', sprintf('/admin/packs/%d', (int) $pack->getId()));
        $this->client->submit($crawler->filter('.list-group-item')->eq(0)->selectButton('Supprimer')->form());

        self::assertResponseRedirects();

        $remaining = $this->mediaRepository->findByPackOrdered($pack);
        self::assertSame([0, 1], array_map(static fn (Media $m): int => $m->getPosition(), $remaining));
    }

    public function testEditMedia(): void
    {
        $pack = $this->packFactory->createPack();
        $media = $this->packFactory->createMedia($pack, 0, 'Ancien titre');

        $this->client->request('GET', sprintf('/admin/medias/%d/modifier', (int) $media->getId()));
        $this->client->submitForm('Enregistrer', [
            'media[title]' => 'Nouveau titre',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'Contenu modifié',
        ]);

        self::assertResponseRedirects();
        self::assertNotNull($this->mediaRepository->findOneBy(['title' => 'Nouveau titre']));
    }

    private function createUploadedImage(): UploadedFile
    {
        $directory = sys_get_temp_dir().'/'.uniqid('media-test-', true);
        mkdir($directory);
        $path = $directory.'/souvenir.png';
        // Un PNG 1x1 valide, pour que la contrainte de fichier soit réellement exercée.
        file_put_contents($path, (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        ));

        return new UploadedFile($path, 'souvenir.png', 'image/png', null, true);
    }

    private function removeUploadedFile(Media $media): void
    {
        $path = self::getContainer()->getParameter('app.upload.media_directory').'/'.$media->getFilePath();

        if (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * @return list<string>
     */
    private function titlesInOrder(\App\Entity\Pack $pack): array
    {
        return array_map(
            static fn (Media $m): string => $m->getTitle(),
            $this->mediaRepository->findByPackOrdered($pack),
        );
    }
}
