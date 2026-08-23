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

    /**
     * L'accroche attendue dans le fil, écrite en dur : la comparer au retour de
     * headline() ne prouverait rien, les deux venant de la même source.
     *
     * @return iterable<string, array{AppKind, string}>
     */
    public static function headlines(): iterable
    {
        $expected = [
            'uber_eats' => 'Ta commande est arrivée 🎁',
            'instagram' => 'dodo.du.passe a publié une photo',
            'tinder' => 'C\'est un match ! 💘',
            'doctolib' => 'Rappel de rendez-vous · Dr Passé',
            'tiktok' => '@dodo.du.passe · ta vidéo cartonne 🔥',
            'snapchat' => 'Dodo du passé t\'a envoyé un Snap 👻',
            'x' => 'Dodo du passé a posté',
            'bereal' => '⚠️ Time to BeReal ⚠️',
            'youtube' => 'Dodo du passé a mis en ligne une vidéo',
            'netflix' => 'Nouveauté recommandée pour toi',
            'spotify' => 'Nouveau titre dans « Tes années lycée »',
            'whatsapp' => 'Dodo du passé t\'a envoyé un message',
            'messenger' => 'Dodo du passé t\'a envoyé un message',
            'imessage' => 'Dodo du passé t\'a envoyé un message',
            'duolingo' => '🦉 Ça fait 11 jours que tu n\'as pas pratiqué',
            'hinge' => 'Quelqu\'un a aimé ta réponse',
            'bumble' => 'Nouveau match 🐝 · plus que 23 h',
            'uber' => 'Ta course avec Dodo du passé est terminée',
            'deliveroo' => 'Ta commande Chez Dodo est livrée',
            'burger_king' => 'Commande n° 2015 prête 👑',
            'mcdonalds' => 'Commande D42 prête 🍟',
            'waze' => 'Itinéraire vers ton adolescence',
            'revolut' => 'Dodo du passé · transaction',
            'paypal' => 'Tu as reçu un paiement de Dodo du passé',
            'lydia' => 'Dodo du passé t\'a envoyé de l\'aura 💸',
            'meteo' => 'Alerte météo : Nuageux avec risque de drama',
            'calendar' => 'Rappel · Samedi 23 août 2015',
        ];

        foreach (AppKind::cases() as $appKind) {
            yield $appKind->value => [$appKind, $expected[$appKind->value]];
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
        // Dans main, pas dans body : le <title> de l'onglet contient déjà le
        // titre du média et validerait un écran qui ne l'affiche nulle part.
        self::assertSelectorTextContains('main', 'Brouillon '.$appKind->value);
    }

    #[DataProvider('headlines')]
    public function testEachAppAnnouncesItselfInItsOwnWords(AppKind $appKind, string $expected): void
    {
        $registry = self::getContainer()->get(AppDetailsRegistry::class);

        self::assertSame($expected, $appKind->headline($registry->defaultsFor($appKind)));
    }

    /**
     * Les messageries et leur préfixe CSS.
     *
     * @return iterable<string, array{AppKind, string}>
     */
    public static function messagingApps(): iterable
    {
        yield 'whatsapp' => [AppKind::WHATSAPP, 'wa'];
        yield 'messenger' => [AppKind::MESSENGER, 'ms'];
        yield 'imessage' => [AppKind::IMESSAGE, 'im'];
    }

    #[DataProvider('messagingApps')]
    public function testAConversationBecomesBubbles(AppKind $appKind, string $prefix): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $crawler = $this->client->request('POST', sprintf('/admin/notifications/nouveau/%s/apercu', $appKind->value), [
            'media' => [
                'title' => 'Le message de 4 h 12',
                'description' => 'La dernière réplique du contact.',
                'type' => MediaType::TEXT->value,
                'textContent' => 'jsuis dehors depuis 20 min',
                'appData' => [
                    'contact' => 'Dodo du passé',
                    'conversation' => "c'est qui ???\nmoi: toi, en 2015\n\nmoi: il était 4h12",
                ],
            ],
        ]);

        self::assertResponseIsSuccessful();

        $bubbles = $crawler->filter(sprintf('.%s-chat .%1$s-b', $prefix));
        self::assertGreaterThan(0, $bubbles->count(), 'La conversation doit produire des bulles.');

        $texts = $bubbles->each(static fn ($node): string => trim($node->text()));
        $joined = implode(' | ', $texts);

        self::assertStringContainsString('c\'est qui ???', $joined);
        self::assertStringContainsString('toi, en 2015', $joined);
        self::assertStringContainsString('il était 4h12', $joined);
        self::assertStringContainsString('La dernière réplique du contact.', $joined, 'La description ferme la conversation.');
        self::assertStringContainsString('jsuis dehors depuis 20 min', $joined, 'Le souvenir est envoyé dans une bulle.');
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
        self::assertSelectorTextContains('.f-n-fresh .f-m', $media->getTitle());
        self::assertSelectorTextContains('.f-n-fresh .f-btn', $appKind->openLabel());
        self::assertSame($appKind->icon(), trim($crawler->filter('.f-n-fresh .f-app')->text()));

        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $media->getId()));
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body.f-open');
        self::assertSelectorExists('[data-controller="aura-toast"]');
        self::assertSelectorTextContains('main', 'Souvenir '.$appKind->value);
        self::assertSelectorTextContains('main', 'Une description qui doit apparaître quelque part.');
        self::assertSelectorExists('.f-media-text', 'Le souvenir lui-même passe par feed/_media.html.twig.');
    }
}
