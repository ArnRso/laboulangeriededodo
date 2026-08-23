<?php

namespace App\Tests\Functional;

use App\Enum\AppKind;
use App\Enum\MediaType;
use App\Form\AppDetails\AppDetailsRegistry;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Chaque application imitée doit être complète de bout en bout : formulaire
 * admin, aperçu, écran d'ouverture, accroche dans le fil. Ajouter un cas à
 * AppKind suffit pour qu'il soit vérifié ici.
 */
class AppKindCoverageTest extends WebTestCase
{
    private KernelBrowser $client;
    private MediaFactory $mediaFactory;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->mediaFactory = new MediaFactory($entityManager);
        $this->userFactory = new UserFactory($entityManager, $container->get(UserPasswordHasherInterface::class));
    }

    /**
     * @return iterable<string, array{AppKind}>
     */
    public static function appKinds(): iterable
    {
        foreach (AppKind::cases() as $appKind) {
            yield $appKind->value => [$appKind];
        }
    }

    #[DataProvider('appKinds')]
    public function testEveryAppHasItsFiles(AppKind $appKind): void
    {
        $projectDir = self::getContainer()->getParameter('kernel.project_dir');

        self::assertFileExists(sprintf('%s/templates/%s', $projectDir, $appKind->template()));
        self::assertNotSame('', $appKind->label());
        self::assertNotSame('', $appKind->pitch());
        self::assertNotSame('', $appKind->headline([]));
        self::assertNotSame('', $appKind->openLabel());
    }

    public function testEveryAppBelongsToACategory(): void
    {
        $listed = array_merge(...array_values(AppKind::byCategory()));

        self::assertCount(\count(AppKind::cases()), $listed);
        self::assertEqualsCanonicalizing(AppKind::cases(), $listed);
    }

    #[DataProvider('appKinds')]
    public function testTheAdminFormOpensWithDefaults(AppKind $appKind): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $crawler = $this->client->request('GET', sprintf('/admin/notifications/nouveau/%s', $appKind->value));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', $appKind->label());

        $registry = self::getContainer()->get(AppDetailsRegistry::class);
        foreach (array_keys($registry->defaultsFor($appKind)) as $field) {
            self::assertCount(1, $crawler->filter(sprintf('[name="media[appData][%s]"]', $field)), sprintf('Le champ « %s » de %s n\'est pas dans le formulaire.', $field, $appKind->label()));
        }
    }

    #[DataProvider('appKinds')]
    public function testTheLivePreviewRendersAnEmptyDraft(AppKind $appKind): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $this->client->request('POST', sprintf('/admin/notifications/nouveau/%s/apercu', $appKind->value), [
            'media' => ['title' => 'Brouillon '.$appKind->value, 'type' => MediaType::IMAGE->value],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Brouillon '.$appKind->value);
    }

    #[DataProvider('appKinds')]
    public function testTheRecipientSeesTheCardAndTheScreen(AppKind $appKind): void
    {
        $recipient = $this->userFactory->createRecipient();
        $this->client->loginUser($recipient);

        $registry = self::getContainer()->get(AppDetailsRegistry::class);
        $media = $this->mediaFactory->createNotification(0, 'Souvenir '.$appKind->value, $appKind, delayMinutes: 0);
        $media->setDescription('Une description qui doit apparaître quelque part.')->setAppData($registry->defaultsFor($appKind));
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $crawler = $this->client->request('GET', '/mon-espace');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.f-n-fresh .f-t', $appKind->headline($media->getAppData()));
        self::assertSelectorTextContains('.f-n-fresh .f-btn', $appKind->openLabel());
        self::assertSame($appKind->icon(), trim($crawler->filter('.f-n-fresh .f-app')->text()));

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $media->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body.f-open');
        self::assertSelectorExists('[data-controller="aura-toast"]');
        self::assertSelectorTextContains('body', 'Souvenir '.$appKind->value);
        self::assertSelectorTextContains('body', 'Une description qui doit apparaître quelque part.');
        self::assertSelectorExists('.f-media-text', 'Le souvenir lui-même passe par feed/_media.html.twig.');
    }
}
