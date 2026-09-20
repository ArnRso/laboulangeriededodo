<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Tag;
use App\Repository\MediaRepository;
use App\Repository\TagRepository;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TagControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private MediaFactory $mediaFactory;
    private TagRepository $tagRepository;
    private MediaRepository $mediaRepository;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->mediaFactory = new MediaFactory($entityManager);
        $this->tagRepository = $container->get(TagRepository::class);
        $this->mediaRepository = $container->get(MediaRepository::class);

        $this->userFactory = new UserFactory($entityManager, $container->get(UserPasswordHasherInterface::class));
        $this->client->loginUser($this->userFactory->createAdmin());
    }

    public function testTheListShowsEveryTagInAlphabeticalOrder(): void
    {
        $this->mediaFactory->createTag('Voyage');
        $this->mediaFactory->createTag('Blague');
        $this->mediaFactory->createTag('à retravailler');

        $crawler = $this->client->request('GET', '/admin/etiquettes');

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['à retravailler', 'Blague', 'Voyage'],
            $crawler->filter('.list-group-item .fw-semibold')->each(static fn ($node): string => trim($node->text())),
        );
    }

    public function testCreatesATag(): void
    {
        $this->client->request('GET', '/admin/etiquettes');
        $this->client->submitForm('Créer', ['tag[name]' => '  Souvenirs  ']);

        self::assertResponseRedirects('/admin/etiquettes');

        $tag = $this->tagRepository->findOneByName('Souvenirs');
        self::assertNotNull($tag);
        self::assertSame('Souvenirs', $tag->getName(), 'Les espaces autour du nom sont retirés.');
    }

    public function testATagWithoutANameIsRejected(): void
    {
        $this->client->request('GET', '/admin/etiquettes');
        $this->client->submitForm('Créer', ['tag[name]' => '   ']);

        self::assertResponseIsUnprocessable();
        self::assertCount(0, $this->tagRepository->findAll());
    }

    public function testTheSameNameIsRefusedWhateverTheCase(): void
    {
        $this->mediaFactory->createTag('Voyage');

        $this->client->request('GET', '/admin/etiquettes');
        $this->client->submitForm('Créer', ['tag[name]' => 'VOYAGE']);

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains('.invalid-feedback', 'Une étiquette porte déjà ce nom.');
        self::assertCount(1, $this->tagRepository->findAll());
    }

    public function testANameLongerThanTheLimitIsRejected(): void
    {
        $this->client->request('GET', '/admin/etiquettes');
        $this->client->submitForm('Créer', ['tag[name]' => str_repeat('a', Tag::MAX_NAME_LENGTH + 1)]);

        self::assertResponseIsUnprocessable();
        self::assertCount(0, $this->tagRepository->findAll());
    }

    public function testRenamesATagWithoutLosingItsNotifications(): void
    {
        $tag = $this->mediaFactory->createTag('Voyage');
        $media = $this->mediaFactory->createNotification(0, 'Un voyage', tags: [$tag]);

        $this->client->request('GET', sprintf('/admin/etiquettes/%d/modifier', (int) $tag->getId()));
        $this->client->submitForm('Enregistrer', ['tag[name]' => 'Escapades']);

        self::assertResponseRedirects('/admin/etiquettes');

        $reloaded = $this->mediaRepository->find((int) $media->getId());
        self::assertNotNull($reloaded);
        self::assertSame(['Escapades'], $reloaded->getTags()->map(static fn (Tag $t): string => $t->getName())->toArray());
    }

    public function testRenamingToAnExistingNameIsRefused(): void
    {
        $this->mediaFactory->createTag('Voyage');
        $cadeau = $this->mediaFactory->createTag('Cadeau');

        $this->client->request('GET', sprintf('/admin/etiquettes/%d/modifier', (int) $cadeau->getId()));
        $this->client->submitForm('Enregistrer', ['tag[name]' => 'Voyage']);

        self::assertResponseIsUnprocessable();
        self::assertSame('Cadeau', $this->tagRepository->find((int) $cadeau->getId())?->getName());
    }

    public function testKeepingItsOwnNameIsNotSeenAsADuplicate(): void
    {
        $tag = $this->mediaFactory->createTag('Voyage');

        $this->client->request('GET', sprintf('/admin/etiquettes/%d/modifier', (int) $tag->getId()));
        $this->client->submitForm('Enregistrer', ['tag[name]' => 'voyage']);

        self::assertResponseRedirects('/admin/etiquettes');
        self::assertSame('voyage', $this->tagRepository->find((int) $tag->getId())?->getName());
    }

    public function testDeletingATagLeavesItsNotificationsInPlace(): void
    {
        $tag = $this->mediaFactory->createTag('Voyage');
        $media = $this->mediaFactory->createNotification(0, 'Un voyage', tags: [$tag]);

        $crawler = $this->client->request('GET', '/admin/etiquettes');
        $this->client->submit($crawler->filter('form[action$="/supprimer"]')->form());

        self::assertResponseRedirects('/admin/etiquettes');
        self::assertCount(0, $this->tagRepository->findAll());

        $reloaded = $this->mediaRepository->find((int) $media->getId());
        self::assertNotNull($reloaded, 'La notification survit à son étiquette.');
        self::assertCount(0, $reloaded->getTags());
    }

    public function testDeletingRequiresAValidCsrfToken(): void
    {
        $tag = $this->mediaFactory->createTag('Voyage');

        $this->client->request('POST', sprintf('/admin/etiquettes/%d/supprimer', (int) $tag->getId()), ['_token' => 'faux']);

        // Un jeton invalide invalide la session : le firewall renvoie vers la
        // connexion plutôt que de servir un 403.
        self::assertResponseRedirects();
        self::assertCount(1, $this->tagRepository->findAll(), 'L\'étiquette est toujours là.');
    }

    public function testTheListSaysHowManyNotificationsUseATag(): void
    {
        $tag = $this->mediaFactory->createTag('Voyage');
        $this->mediaFactory->createNotification(0, 'Un', tags: [$tag]);
        $this->mediaFactory->createNotification(1, 'Deux', tags: [$tag]);
        $this->mediaFactory->createTag('Inutilisée');

        $crawler = $this->client->request('GET', '/admin/etiquettes');

        self::assertStringContainsString('pas encore utilisée', $crawler->filter('.list-group-item')->first()->text());
        self::assertStringContainsString('2 notifications', $crawler->filter('.list-group-item')->last()->text());
    }

    public function testARecipientCannotReachTheTags(): void
    {
        $this->client->loginUser($this->userFactory->createRecipient());

        $this->client->request('GET', '/admin/etiquettes');

        self::assertResponseStatusCodeSame(403);
    }
}
