<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Media;
use App\Entity\Pack;
use App\Entity\User;
use App\Service\ProgressionService;
use App\Tests\Factory\PackFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class JourneyControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private PackFactory $packFactory;
    private UserFactory $userFactory;
    private ProgressionService $progressionService;
    private EntityManagerInterface $entityManager;
    private User $dorian;

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
        $this->progressionService = $container->get(ProgressionService::class);

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

    public function testNavigationShowsTheRecipientLinksOnly(): void
    {
        $crawler = $this->client->request('GET', '/mon-espace');

        self::assertSelectorTextContains('.navbar', 'Mes souvenirs');
        self::assertSelectorTextNotContains('.navbar', 'Packs', 'L\'administration ne doit pas apparaître.');
        self::assertSelectorTextNotContains('.navbar', 'Destinataire');
        self::assertSelectorExists('.navbar .dropdown-menu a[href="/deconnexion"]');
        self::assertCount(
            0,
            $crawler->filter('.navbar a[href="/admin/mon-compte"]'),
            'Le destinataire n\'a pas accès à l\'espace admin.',
        );
    }

    public function testChoiceScreenListsAvailablePacks(): void
    {
        $pack = $this->packFactory->createPack('Nos années lycée');
        $this->packFactory->createMedias($pack, 3);

        $this->client->request('GET', '/mon-espace');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Nos années lycée');
    }

    public function testUnpublishedPackIsNotOffered(): void
    {
        $hidden = $this->packFactory->createPack('Pack masqué', published: false);
        $this->packFactory->createMedias($hidden, 2);

        $this->client->request('GET', '/mon-espace');

        self::assertSelectorTextNotContains('body', 'Pack masqué');
    }

    public function testStartingAPackShowsItsMediaList(): void
    {
        $pack = $this->packFactory->createPack('Nos années lycée');
        $this->packFactory->createMedias($pack, 3);

        $crawler = $this->client->request('GET', '/mon-espace');
        $this->client->submit($crawler->selectButton('Commencer')->form());

        self::assertResponseRedirects('/mon-espace');
        $this->client->followRedirect();

        self::assertSelectorTextContains('body', 'Nos années lycée');
        self::assertSelectorTextContains('body', 'Un nouveau souvenir t\'attend');
    }

    public function testStartRequiresValidCsrfToken(): void
    {
        $pack = $this->packFactory->createPack();
        $this->packFactory->createMedias($pack, 2);

        $this->client->request('POST', sprintf('/mon-espace/packs/%d/commencer', (int) $pack->getId()), [
            '_token' => 'jeton-invalide',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
        self::assertNull($this->progressionService->getActiveProgress($this->dorian));
    }

    public function testFirstMediaCanBeOpenedImmediately(): void
    {
        [$pack, $medias] = $this->startedPack(3);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[0]->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Média 1');
    }

    public function testLockedMediaCannotBeOpenedByGuessingItsUrl(): void
    {
        [$pack, $medias] = $this->startedPack(3);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[2]->getId()));

        self::assertResponseRedirects('/mon-espace');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert', 'pas encore accessible');
    }

    public function testSecondMediaStaysLockedUntilTheDelayHasElapsed(): void
    {
        [$pack, $medias] = $this->startedPack(3, unlockDelayHours: 24);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[0]->getId()));
        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[1]->getId()));

        self::assertResponseRedirects('/mon-espace');
    }

    public function testSecondMediaOpensOnceTheDelayHasElapsed(): void
    {
        [$pack, $medias] = $this->startedPack(3, unlockDelayHours: 24);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[0]->getId()));

        $progress = $this->progressionService->getActiveProgress($this->dorian);
        self::assertNotNull($progress);
        $progress->setLastOpenedAt(new \DateTimeImmutable('-25 hours'));
        $this->entityManager->flush();

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[1]->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Média 2');
    }

    public function testMediaOfAnotherPackIsRefused(): void
    {
        [$pack, $medias] = $this->startedPack(2);

        $other = $this->packFactory->createPack('Autre pack', position: 1);
        $otherMedias = $this->packFactory->createMedias($other, 2);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $otherMedias[0]->getId()));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testOpenedMediaStaysAccessible(): void
    {
        [$pack, $medias] = $this->startedPack(3, unlockDelayHours: 24);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[0]->getId()));
        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[0]->getId()));

        self::assertResponseIsSuccessful();
    }

    public function testCompletingAPackOffersTheNextOne(): void
    {
        [$pack, $medias] = $this->startedPack(1);

        $next = $this->packFactory->createPack('Pack suivant', position: 1);
        $this->packFactory->createMedias($next, 2);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[0]->getId()));

        $this->client->request('GET', '/mon-espace');

        self::assertSelectorTextContains('body', 'Pack suivant');
        self::assertSelectorTextContains('body', 'Collections terminées');
    }

    public function testHistoryOnlyListsOpenedMedias(): void
    {
        [$pack, $medias] = $this->startedPack(3, unlockDelayHours: 24);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[0]->getId()));

        $this->client->request('GET', sprintf('/mon-espace/packs/%d', (int) $pack->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Média 1');
        self::assertSelectorTextNotContains('body', 'Média 2', 'Un média non ouvert ne doit pas être révélé.');
    }

    public function testHistoryOfANeverStartedPackIsRefused(): void
    {
        $pack = $this->packFactory->createPack('Jamais commencé');
        $this->packFactory->createMedias($pack, 2);

        $this->client->request('GET', sprintf('/mon-espace/packs/%d', (int) $pack->getId()));

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCountdownIsShownForTheLockedMedia(): void
    {
        [$pack, $medias] = $this->startedPack(3, unlockDelayHours: 24);

        $this->client->request('GET', sprintf('/mon-espace/medias/%d', (int) $medias[0]->getId()));
        $this->client->request('GET', '/mon-espace');

        self::assertSelectorExists('[data-controller="countdown"]');
    }

    /**
     * @return array{0: Pack, 1: list<Media>}
     */
    private function startedPack(int $mediaCount, int $unlockDelayHours = 24): array
    {
        $pack = $this->packFactory->createPack(unlockDelayHours: $unlockDelayHours);
        $medias = $this->packFactory->createMedias($pack, $mediaCount);
        $this->progressionService->startPack($this->dorian, $pack);

        return [$pack, $medias];
    }
}
