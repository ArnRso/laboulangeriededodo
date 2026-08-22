<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Enum\AppKind;
use App\Repository\MediaAccessRepository;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FeedControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private MediaFactory $mediaFactory;
    private UserFactory $userFactory;
    private MediaAccessRepository $accessRepository;
    private EntityManagerInterface $entityManager;
    private User $dorian;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->accessRepository = $container->get(MediaAccessRepository::class);
        $this->mediaFactory = new MediaFactory($this->entityManager);
        $this->userFactory = new UserFactory($this->entityManager, $container->get(UserPasswordHasherInterface::class));

        $this->dorian = $this->userFactory->createRecipient();
        $this->client->loginUser($this->dorian);
    }

    public function testAnonymousVisitorIsRedirectedToLogin(): void
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request('GET', '/mon-espace');

        self::assertResponseRedirects();
        self::assertStringContainsString('/connexion', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAdminIsSentToTheAdmin(): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $this->client->request('GET', '/mon-espace');

        self::assertResponseRedirects('/admin/notifications');
    }

    public function testEmptyFeed(): void
    {
        $this->client->request('GET', '/mon-espace');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Aucune notification');
        self::assertSelectorTextContains('.f-aura', '0 aura');
    }

    public function testFirstNotificationIsFreshAndTheSecondIsWaiting(): void
    {
        $this->mediaFactory->createFeed(3);

        $this->client->request('GET', '/mon-espace');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('.f-n-fresh');
        self::assertSelectorTextContains('.f-n-fresh', 'Notification 1');
        self::assertSelectorTextContains('body', 'part quand tu auras ouvert celle-ci');
        self::assertSelectorNotExists('[data-controller="countdown"]', 'Le chrono ne court pas tant que la fraîche n\'est pas ouverte.');
    }

    public function testFeedUsesItsOwnLayoutWithoutBootstrap(): void
    {
        $this->mediaFactory->createFeed(1);

        $crawler = $this->client->request('GET', '/mon-espace');

        // Le module stimulus_bootstrap.js porte ce nom : seule la feuille de style compte.
        self::assertCount(0, $crawler->filter('link[rel="stylesheet"][href*="bootstrap"]'));
        self::assertCount(1, $crawler->filter('link[rel="stylesheet"][href*="feed"]'));
        self::assertSelectorExists('[data-controller="clock"]');
        self::assertSelectorNotExists('nav.navbar');
    }

    public function testOpeningRecordsTheAccessAndRendersTheApp(): void
    {
        $medias = $this->mediaFactory->createFeed(2);

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Commande livrée');
        self::assertSelectorTextContains('body', 'Notification 1');
        self::assertSelectorExists('[data-controller="aura-toast"]', 'Le gain d\'aura s\'affiche à la première ouverture.');
        self::assertNotNull($this->accessRepository->findOneByUserAndMedia($this->dorian, $medias[0]));
    }

    public function testReopeningDoesNotShowTheToastAgain(): void
    {
        $medias = $this->mediaFactory->createFeed(1);

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));
        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('[data-controller="aura-toast"]');
    }

    public function testEachAppHasItsOwnScreen(): void
    {
        $expectations = [
            [AppKind::INSTAGRAM, 'Publication'],
            [AppKind::TINDER, 'C\'est un match'],
            [AppKind::DOCTOLIB, 'Rendez-vous honoré'],
        ];

        foreach ($expectations as $position => [$appKind, $text]) {
            $media = $this->mediaFactory->createNotification($position, sprintf('Notif %s', $appKind->value), $appKind, delayMinutes: 0);

            $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $media->getId()));

            self::assertResponseIsSuccessful();
            self::assertSelectorTextContains('body', $text);
        }
    }

    public function testLockedNotificationCannotBeOpenedByGuessingItsUrl(): void
    {
        $medias = $this->mediaFactory->createFeed(3);

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[2]->getId()));

        self::assertResponseRedirects('/mon-espace');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.f-flash', 'pas encore arrivée');
        self::assertNull($this->accessRepository->findOneByUserAndMedia($this->dorian, $medias[2]));
    }

    public function testSecondStaysLockedUntilTheDelayHasElapsed(): void
    {
        $medias = $this->mediaFactory->createFeed(3);

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));
        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[1]->getId()));

        self::assertResponseRedirects('/mon-espace');
    }

    public function testAfterOpeningTheCountdownRuns(): void
    {
        $medias = $this->mediaFactory->createFeed(3);

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));
        $this->client->request('GET', '/mon-espace');

        self::assertSelectorNotExists('.f-n-fresh');
        self::assertSelectorExists('[data-controller="countdown"]');
        self::assertSelectorTextContains('.f-grp', 'En route');
        self::assertSelectorTextContains('.f-n-seen', 'Notification 1');
        self::assertSelectorTextContains('.f-aura', '+100 aujourd');
    }

    public function testSecondOpensOnceTheDelayHasElapsed(): void
    {
        $medias = $this->mediaFactory->createFeed(3);

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));

        $access = $this->accessRepository->findOneByUserAndMedia($this->dorian, $medias[0]);
        self::assertNotNull($access);
        $access->setOpenedAt(new \DateTimeImmutable('-25 hours'));
        $this->entityManager->flush();

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[1]->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Notification 2');
    }

    public function testNegativeAuraIsShownAsALoss(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'La coupe de 2015', AppKind::TINDER, auraPoints: -500);

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $media->getId()));
        self::assertSelectorTextContains('.f-toast', '-500 aura');

        $this->client->request('GET', '/mon-espace');
        self::assertSelectorTextContains('.f-aura', '-500 aura');
        self::assertSelectorExists('.f-aurachip-neg');
    }

    public function testSeenNotificationsBeyondThreeAreFoldedInAStack(): void
    {
        $medias = $this->mediaFactory->createFeed(5, delayMinutes: 0);

        foreach ($medias as $media) {
            $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $media->getId()));
        }

        $crawler = $this->client->request('GET', '/mon-espace');

        self::assertCount(5, $crawler->filter('.f-n-seen'));
        self::assertCount(2, $crawler->filter('.f-n-seen[hidden]'), 'Seules les trois plus récentes sont visibles.');
        self::assertSelectorTextContains('.f-stack', '2 autres notifications');
        self::assertSelectorTextContains('body', 'Fin de saison');
    }

    public function testUnpublishedNotificationIsInvisible(): void
    {
        $this->mediaFactory->createNotification(0, 'Brouillon secret', published: false);
        $this->mediaFactory->createNotification(1, 'Visible');

        $this->client->request('GET', '/mon-espace');

        self::assertSelectorTextNotContains('body', 'Brouillon secret');
        self::assertSelectorTextContains('.f-n-fresh', 'Visible');
    }

    public function testAdminPreviewDoesNotRecordAnAccess(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'À prévisualiser', AppKind::DOCTOLIB);
        $admin = $this->userFactory->createAdmin();
        $this->client->loginUser($admin);

        $this->client->request('GET', sprintf('/admin/notifications/%d/apercu', (int) $media->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Rendez-vous honoré');
        self::assertSelectorTextContains('.f-preview-bar', 'Aperçu');
        self::assertNull($this->accessRepository->findOneByUserAndMedia($admin, $media));
    }

    public function testRecipientCannotUseTheAdminPreview(): void
    {
        $media = $this->mediaFactory->createNotification(0);

        $this->client->request('GET', sprintf('/admin/notifications/%d/apercu', (int) $media->getId()));

        self::assertResponseStatusCodeSame(403);
    }

    public function testMediaIdsAreNotLeakedThroughTheSeenList(): void
    {
        $medias = $this->mediaFactory->createFeed(2);
        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));

        $crawler = $this->client->request('GET', '/mon-espace');

        $links = $crawler->filter('a.f-n')->each(static fn ($node): string => (string) $node->attr('href'));
        self::assertSame([sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId())], $links, 'Seule la consultée est un lien ; la suivante verrouillée n\'expose pas d\'URL.');
    }
}
