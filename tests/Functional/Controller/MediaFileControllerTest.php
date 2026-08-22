<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Media;
use App\Enum\MediaType;
use App\Tests\Factory\PackFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class MediaFileControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private PackFactory $packFactory;
    private UserFactory $userFactory;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->packFactory = new PackFactory($this->entityManager);
        $this->userFactory = new UserFactory(
            $this->entityManager,
            $container->get(UserPasswordHasherInterface::class),
        );
    }

    public function testAnonymousVisitorIsRedirectedToLogin(): void
    {
        $media = $this->createFileMedia();

        $this->client->request('GET', sprintf('/medias/%d/fichier', (int) $media->getId()));

        self::assertResponseRedirects();
        self::assertStringContainsString(
            '/connexion',
            (string) $this->client->getResponse()->headers->get('Location'),
        );
    }

    public function testAdminCanDownloadAnyFile(): void
    {
        $media = $this->createFileMedia();
        $this->client->loginUser($this->userFactory->createAdmin());

        $this->client->request('GET', sprintf('/medias/%d/fichier', (int) $media->getId()));

        self::assertResponseIsSuccessful();
    }

    public function testRecipientWithoutProgressIsDenied(): void
    {
        $media = $this->createFileMedia();
        $this->client->loginUser($this->userFactory->createRecipient());

        $this->client->request('GET', sprintf('/medias/%d/fichier', (int) $media->getId()));

        self::assertResponseStatusCodeSame(
            Response::HTTP_FORBIDDEN,
            'Sans progression sur le pack, le fichier reste inaccessible.',
        );
    }

    public function testMissingFileReturnsNotFound(): void
    {
        $pack = $this->packFactory->createPack();
        $media = $this->packFactory->createMedia($pack, 0, 'Sans fichier');
        $media->setType(MediaType::IMAGE)->setFilePath('fichier-absent.png');
        $this->entityManager->flush();

        $this->client->loginUser($this->userFactory->createAdmin());
        $this->client->request('GET', sprintf('/medias/%d/fichier', (int) $media->getId()));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testMediaWithoutFilePathReturnsNotFound(): void
    {
        $pack = $this->packFactory->createPack();
        $media = $this->packFactory->createMedia($pack, 0, 'Texte');

        $this->client->loginUser($this->userFactory->createAdmin());
        $this->client->request('GET', sprintf('/medias/%d/fichier', (int) $media->getId()));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createFileMedia(): Media
    {
        $pack = $this->packFactory->createPack();
        $media = $this->packFactory->createMedia($pack, 0, 'Une photo');
        $media->setType(MediaType::IMAGE)->setFilePath('test-fixture.png');

        $directory = $this->uploadDirectory();

        if (!is_dir($directory)) {
            mkdir($directory, 0o777, true);
        }

        file_put_contents($directory.'/test-fixture.png', 'contenu');
        $this->entityManager->flush();

        return $media;
    }

    private function uploadDirectory(): string
    {
        return self::getContainer()->getParameter('app.upload.media_directory');
    }

    protected function tearDown(): void
    {
        $path = $this->uploadDirectory().'/test-fixture.png';

        if (is_file($path)) {
            unlink($path);
        }

        parent::tearDown();
    }
}
