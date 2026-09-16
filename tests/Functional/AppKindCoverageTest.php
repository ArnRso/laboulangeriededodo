<?php

namespace App\Tests\Functional;

use App\Enum\AppFieldKind;
use App\Enum\AppKind;
use App\Enum\MediaType;
use App\Form\AppDetails\AppDetailsRegistry;
use App\Repository\MediaRepository;
use App\Service\Dressing\AppFieldCatalog;
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
            'lydia' => 'Dodo du passé t\'a envoyé un virement 💸',
            'meteo' => 'Alerte météo : Nuageux avec risque de drama',
            'calendar' => 'Rappel · Samedi 23 août 2015',
            'horoscope' => '🔮 Ton Balance du jour est arrivé',
            'quiz' => 'Nouveau quiz : À quel point tu connais ton passé ?',
            'pornhub' => 'DodoDuPasse a mis en ligne une vidéo',
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
        self::assertSelectorTextContains('main', 'Souvenir '.$appKind->value);
        self::assertSelectorTextContains('main', 'Une description qui doit apparaître quelque part.');
        self::assertSelectorExists('.f-media-text', 'Le souvenir lui-même passe par feed/_media.html.twig.');
    }

    /**
     * Chaque endroit qui affiche du texte doit être pilotable séparément : en
     * remplissant tous les champs de détails, ni le titre ni la description du
     * média ne doivent plus apparaître à l'écran.
     */
    #[DataProvider('appKinds')]
    public function testEveryTextSlotCanBeOverridden(AppKind $appKind): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $registry = self::getContainer()->get(AppDetailsRegistry::class);
        $appData = [];

        foreach ($registry->defaultsFor($appKind) as $field => $default) {
            $appData[$field] = match (true) {
                \is_bool($default) => $default,
                \is_int($default) => $default,
                '' === $default => 'Valeur propre à '.$field,
                default => $default,
            };
        }

        $crawler = $this->client->request('POST', sprintf('/admin/notifications/nouveau/%s/apercu', $appKind->value), [
            'media' => [
                'title' => 'TITRE-DU-MEDIA',
                'description' => 'DESCRIPTION-DU-MEDIA',
                'type' => MediaType::TEXT->value,
                'textContent' => 'Le souvenir lui-même.',
                'appData' => $appData,
            ],
        ]);

        self::assertResponseIsSuccessful();

        $main = $crawler->filter('main')->text();
        self::assertStringNotContainsString('TITRE-DU-MEDIA', $main, 'Un emplacement affiche encore le titre du média alors que tous les champs sont remplis.');
        self::assertStringNotContainsString('DESCRIPTION-DU-MEDIA', $main, 'Un emplacement affiche encore la description du média alors que tous les champs sont remplis.');
    }
    /**
     * Les entiers rendus sous forme graphique — étoiles allumées, largeur d'une
     * barre — plutôt qu'écrits en toutes lettres à l'écran.
     */
    private const array GRAPHIC_FIELDS = ['stars', 'rating', 'progress'];

    /**
     * Rien de ce que l'admin saisit ne doit rester invisible : chaque champ de
     * détails remplit un emplacement de l'écran. Un champ jamais rendu est un
     * champ que l'admin remplit pour rien.
     */
    #[DataProvider('appKinds')]
    public function testEveryDetailFieldIsRenderedSomewhere(AppKind $appKind): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $registry = self::getContainer()->get(AppDetailsRegistry::class);
        $defaults = $registry->defaultsFor($appKind);

        $appData = [];
        $markers = [];

        foreach ($defaults as $field => $default) {
            if (\is_bool($default)) {
                $appData[$field] = true;

                continue;
            }

            // Certains entiers ne se lisent pas en chiffres : une note de 3
            // allume trois étoiles, une progression de 73 % fixe une largeur.
            // On les vérifie sur ce qu'ils produisent, pas sur leur valeur.
            if (\is_int($default)) {
                $appData[$field] = 73;

                if (!\in_array($field, self::GRAPHIC_FIELDS, true)) {
                    $markers[$field] = '73';
                }

                continue;
            }

            $marker = sprintf('Zz%sZz', ucfirst($field));
            $appData[$field] = $marker;
            $markers[$field] = $marker;
        }

        $crawler = $this->client->request('POST', sprintf('/admin/notifications/nouveau/%s/apercu', $appKind->value), [
            'media' => [
                'title' => 'Titre du média',
                'description' => 'Description du média.',
                'type' => MediaType::TEXT->value,
                'textContent' => 'Le souvenir lui-même.',
                'appData' => $appData,
            ],
        ]);

        self::assertResponseIsSuccessful();

        $screen = $crawler->filter('body')->text();

        foreach ($markers as $field => $marker) {
            self::assertStringContainsString(
                $marker,
                $screen,
                sprintf('Le champ « %s » de %s ne s\'affiche nulle part sur l\'écran.', $field, $appKind->label()),
            );
        }
    }

    /**
     * @return iterable<string, array{AppKind, string, string}>
     */
    public static function graphicFields(): iterable
    {
        yield 'uber_eats stars' => [AppKind::UBER_EATS, 'stars', '.ue-stars'];
        yield 'uber rating' => [AppKind::UBER, 'rating', '.ub-stars'];
        yield 'spotify progress' => [AppKind::SPOTIFY, 'progress', '.sp-progress-bar'];
    }

    /**
     * Les champs rendus graphiquement se vérifient sur leur effet : trois
     * étoiles allumées sur cinq, une barre remplie au tiers.
     */
    #[DataProvider('graphicFields')]
    public function testAGraphicFieldChangesWhatIsDrawn(AppKind $appKind, string $field, string $selector): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $registry = self::getContainer()->get(AppDetailsRegistry::class);
        $rendered = [];

        foreach ([1, 4] as $value) {
            $crawler = $this->client->request('POST', sprintf('/admin/notifications/nouveau/%s/apercu', $appKind->value), [
                'media' => [
                    'title' => 'Titre',
                    'type' => MediaType::TEXT->value,
                    'textContent' => 'Souvenir',
                    'appData' => [...$registry->defaultsFor($appKind), $field => $value],
                ],
            ]);

            self::assertResponseIsSuccessful();
            $rendered[$value] = $crawler->filter($selector)->html();
        }

        self::assertNotSame($rendered[1], $rendered[4], sprintf('Changer « %s » ne change rien à ce qui est dessiné.', $field));
    }

    /**
     * Le catalogue lit le formulaire de chaque app : il doit y trouver
     * exactement les champs que ses défauts déclarent, avec une nature
     * cohérente avec la valeur par défaut.
     */
    #[DataProvider('appKinds')]
    public function testTheFieldCatalogMatchesTheDefaults(AppKind $appKind): void
    {
        $registry = self::getContainer()->get(AppDetailsRegistry::class);
        $catalog = self::getContainer()->get(AppFieldCatalog::class);

        $defaults = $registry->defaultsFor($appKind);
        $fields = $catalog->fieldsFor($appKind);

        self::assertSame(array_keys($defaults), array_map(static fn ($field): string => $field->name, $fields));

        foreach ($fields as $field) {
            self::assertNotSame('', $field->label, sprintf('Le champ « %s » de %s n\'a pas de libellé.', $field->name, $appKind->label()));

            if (\is_bool($defaults[$field->name])) {
                self::assertSame(AppFieldKind::CHECKBOX, $field->kind, $field->name);
            } elseif (\is_int($defaults[$field->name])) {
                self::assertContains($field->kind, [AppFieldKind::INTEGER, AppFieldKind::CHOICE], $field->name);
            }
        }
    }

    #[DataProvider('appKinds')]
    public function testTheDressingFormOffersEveryField(AppKind $appKind): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());
        $draft = $this->mediaFactory->createDraft(0, 'À habiller');

        $crawler = $this->client->request('GET', sprintf('/admin/notifications/%d/habiller/%s', (int) $draft->getId(), $appKind->value));

        self::assertResponseIsSuccessful();

        $registry = self::getContainer()->get(AppDetailsRegistry::class);
        foreach (array_keys($registry->defaultsFor($appKind)) as $field) {
            self::assertGreaterThan(0, $crawler->filter(sprintf('[name^="dress[fields][%s]["]', $field))->count(), sprintf('Le champ « %s » de %s n\'est pas proposé à l\'habillage.', $field, $appKind->label()));
        }
    }

    /**
     * Sans aucun mapping, l'habillage vaut les défauts de l'app : rien ne
     * peut bloquer le passage d'un brouillon à n'importe quelle app.
     */
    #[DataProvider('appKinds')]
    public function testADraftCanBeDressedAsAnyApp(AppKind $appKind): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());
        $draft = $this->mediaFactory->createDraft(0, 'Brouillon '.$appKind->value);

        $this->client->request('GET', sprintf('/admin/notifications/%d/habiller/%s', (int) $draft->getId(), $appKind->value));
        $this->client->submitForm('Habiller en '.$appKind->label());

        self::assertResponseRedirects(sprintf('/admin/notifications/%d/modifier', (int) $draft->getId()));

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->clear();
        $dressed = self::getContainer()->get(MediaRepository::class)->find((int) $draft->getId());
        self::assertNotNull($dressed);
        self::assertSame($appKind, $dressed->getAppKind());
        self::assertSame(self::getContainer()->get(AppDetailsRegistry::class)->defaultsFor($appKind), $dressed->getAppData());

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }
}
