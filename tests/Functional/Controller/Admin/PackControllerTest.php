<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Repository\PackRepository;
use App\Tests\Factory\PackFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PackControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private PackFactory $packFactory;
    private PackRepository $packRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->packFactory = new PackFactory($entityManager);
        $this->packRepository = $container->get(PackRepository::class);

        $userFactory = new UserFactory($entityManager, $container->get(UserPasswordHasherInterface::class));
        $this->client->loginUser($userFactory->createAdmin());
    }

    public function testIndexListsPacks(): void
    {
        $this->packFactory->createPack('Nos années lycée');

        $this->client->request('GET', '/admin/packs');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Nos années lycée');
    }

    public function testCreatePack(): void
    {
        $this->client->request('GET', '/admin/packs/nouveau');
        $this->client->submitForm('Créer', [
            'pack[name]' => 'Les grandes aventures',
            'pack[description]' => 'Des souvenirs',
            'pack[unlockDelayHours]' => 12,
            'pack[published]' => true,
        ]);

        self::assertResponseRedirects();

        $pack = $this->packRepository->findOneBy(['name' => 'Les grandes aventures']);
        self::assertNotNull($pack);
        self::assertSame(12, $pack->getUnlockDelayHours());
        self::assertTrue($pack->isPublished());
    }

    public function testCreateRejectsEmptyName(): void
    {
        $this->client->request('GET', '/admin/packs/nouveau');
        $this->client->submitForm('Créer', [
            'pack[name]' => '',
            'pack[unlockDelayHours]' => 24,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testCreateRejectsNonPositiveDelay(): void
    {
        $this->client->request('GET', '/admin/packs/nouveau');
        $this->client->submitForm('Créer', [
            'pack[name]' => 'Pack',
            'pack[unlockDelayHours]' => 0,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testEditPack(): void
    {
        $pack = $this->packFactory->createPack('Ancien nom');

        $this->client->request('GET', sprintf('/admin/packs/%d/modifier', (int) $pack->getId()));
        $this->client->submitForm('Enregistrer', [
            'pack[name]' => 'Nouveau nom',
            'pack[unlockDelayHours]' => 48,
        ]);

        self::assertResponseRedirects();

        // L'entité est relue : la requête HTTP s'exécute dans un autre contexte Doctrine.
        $updated = $this->packRepository->findOneBy(['name' => 'Nouveau nom']);
        self::assertNotNull($updated);
        self::assertSame(48, $updated->getUnlockDelayHours());
    }

    public function testShowListsMediasInOrder(): void
    {
        $pack = $this->packFactory->createPack();
        $this->packFactory->createMedias($pack, 3);

        $this->client->request('GET', sprintf('/admin/packs/%d', (int) $pack->getId()));

        self::assertResponseIsSuccessful();

        $items = $this->client->getCrawler()->filter('.list-group-item .fw-semibold');
        self::assertSame(
            ['Média 1', 'Média 2', 'Média 3'],
            $items->each(static fn ($node): string => trim($node->text())),
        );
    }

    public function testDeletePackRequiresValidCsrfToken(): void
    {
        $pack = $this->packFactory->createPack();

        $this->client->request('POST', sprintf('/admin/packs/%d/supprimer', (int) $pack->getId()), [
            '_token' => 'jeton-invalide',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertNotNull($this->packRepository->find((int) $pack->getId()), 'Le pack ne doit pas être supprimé.');
    }

    public function testDeletePack(): void
    {
        $pack = $this->packFactory->createPack();
        $packId = (int) $pack->getId();

        $crawler = $this->client->request('GET', sprintf('/admin/packs/%d/modifier', $packId));
        $this->client->submit($crawler->selectButton('Supprimer ce pack')->form());

        self::assertResponseRedirects('/admin/packs');
        self::assertNull($this->packRepository->find($packId));
    }

    public function testUnknownPackReturnsNotFound(): void
    {
        $this->client->request('GET', '/admin/packs/999999');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }
}
