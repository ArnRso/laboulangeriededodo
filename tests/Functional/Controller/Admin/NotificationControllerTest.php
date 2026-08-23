<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Enum\MediaType;
use App\Repository\MediaRepository;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemOperator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NotificationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private MediaFactory $mediaFactory;
    private MediaRepository $mediaRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->mediaFactory = new MediaFactory($entityManager);
        $this->mediaRepository = $container->get(MediaRepository::class);

        $userFactory = new UserFactory($entityManager, $container->get(UserPasswordHasherInterface::class));
        $this->client->loginUser($userFactory->createAdmin());
    }

    public function testIndexListsTheFeedInOrder(): void
    {
        $this->mediaFactory->createFeed(3);

        $crawler = $this->client->request('GET', '/admin/notifications');

        self::assertResponseIsSuccessful();
        self::assertSame(
            ['Notification 1', 'Notification 2', 'Notification 3'],
            $crawler->filter('.list-group-item .fw-semibold')->each(static fn ($node): string => trim($node->text())),
        );
    }

    public function testChoosingStepOffersEveryApp(): void
    {
        $crawler = $this->client->request('GET', '/admin/notifications/nouveau');

        self::assertResponseIsSuccessful();
        self::assertCount(\count(AppKind::cases()), $crawler->filter('a.card'));
        self::assertSelectorTextContains('body', 'Uber Eats');
        self::assertSelectorTextContains('body', 'Doctolib');
    }

    public function testUnknownAppIsNotFound(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/snapchat');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreatesAnUberEatsNotification(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/uber_eats');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'Le premier jour',
            'media[description]' => 'Là où tout a commencé',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'Tu te souviens ?',
            'media[delayMinutes][hours]' => 24,
            'media[delayMinutes][minutes]' => 0,
            'media[auraPoints]' => 100,
            'media[appData][courier]' => 'Dodo du passé',
            'media[appData][trip]' => 'Hier → Aujourd\'hui',
            'media[appData][stars]' => 4,
        ]);

        self::assertResponseRedirects('/admin/notifications');

        $media = $this->mediaRepository->findOneBy(['title' => 'Le premier jour']);
        self::assertNotNull($media);
        self::assertSame(AppKind::UBER_EATS, $media->getAppKind());
        self::assertSame(1440, $media->getDelayMinutes());
        self::assertSame(0, $media->getPosition(), 'La première est en tête du fil.');
        self::assertSame('Dodo du passé', $media->getAppData()['courier']);
        self::assertSame(4, $media->getAppData()['stars']);
        self::assertTrue($media->isPublished());
    }

    public function testAZeroDelayIsAccepted(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/uber_eats');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'Enchaînée',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'Tout de suite après.',
            'media[delayMinutes][hours]' => 0,
            'media[delayMinutes][minutes]' => 0,
        ]);

        self::assertResponseRedirects();

        $media = $this->mediaRepository->findOneBy(['title' => 'Enchaînée']);
        self::assertNotNull($media);
        self::assertSame(0, $media->getDelayMinutes());
    }

    public function testInstagramDetailsAreRequiredAndStored(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/instagram');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'Sans pseudo',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'x',
            'media[appData][username]' => '',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY, 'Le compte qui publie est obligatoire.');
        self::assertNull($this->mediaRepository->findOneBy(['title' => 'Sans pseudo']));

        $this->client->request('GET', '/admin/notifications/nouveau/instagram');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'Avec pseudo',
            'media[type]' => MediaType::LINK->value,
            'media[url]' => 'https://open.spotify.com/',
            'media[appData][username]' => 'dodo.du.passe',
            'media[appData][likesCount]' => 12,
            'media[appData][comments]' => "marie: lol\ndodo: non",
        ]);

        self::assertResponseRedirects();

        $media = $this->mediaRepository->findOneBy(['title' => 'Avec pseudo']);
        self::assertNotNull($media);
        self::assertSame(AppKind::INSTAGRAM, $media->getAppKind());
        self::assertSame('dodo.du.passe', $media->getAppData()['username']);
        self::assertSame(12, $media->getAppData()['likesCount']);
    }

    public function testTinderDramaLevelIsBounded(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/tinder');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'Trop de drama',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'x',
            'media[appData][matchName]' => 'La coupe de 2015',
            'media[appData][dramaLevel]' => 140,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testNegativeAuraIsAllowed(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/tinder');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'La coupe de 2015',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'Preuve photo à venir.',
            'media[auraPoints]' => -500,
            'media[auraMessage]' => 'Désolé.',
            'media[appData][matchName]' => 'La coupe de 2015',
        ]);

        self::assertResponseRedirects();

        $media = $this->mediaRepository->findOneBy(['title' => 'La coupe de 2015']);
        self::assertNotNull($media);
        self::assertSame(-500, $media->getAuraPoints());
    }

    public function testLinkWithoutUrlIsRejected(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/doctolib');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'Sans lien',
            'media[type]' => MediaType::LINK->value,
            'media[appData][practitioner]' => 'Dr Passé',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUploadedFileMustMatchTheChosenType(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/doctolib');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'Fichier incohérent',
            'media[type]' => MediaType::VIDEO->value,
            'media[file]' => $this->createUploadedImage(),
            'media[appData][practitioner]' => 'Dr Passé',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNull($this->mediaRepository->findOneBy(['title' => 'Fichier incohérent']));
    }

    public function testUploadsAnImage(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/instagram');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => 'Une photo',
            'media[type]' => MediaType::IMAGE->value,
            'media[file]' => $this->createUploadedImage(),
            'media[appData][username]' => 'dodo.du.passe',
        ]);

        self::assertResponseRedirects();

        $media = $this->mediaRepository->findOneBy(['title' => 'Une photo']);
        self::assertNotNull($media);
        self::assertNotNull($media->getFilePath());
        self::assertSame('souvenir.png', $media->getOriginalName());

        $publicPath = self::getContainer()->getParameter('kernel.project_dir').'/public/'.$media->getFilePath();
        self::assertFileDoesNotExist($publicPath, 'Un fichier dans public/ contournerait le délai.');
        self::assertTrue($this->mediaStorage()->fileExists($media->getFilePath()), 'Le fichier est rangé dans le stockage Flysystem.');

        $this->removeUploadedFile($media);
    }

    public function testEditKeepsTheAppAndUpdatesDetails(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'Ancien titre', AppKind::DOCTOLIB);
        $media->setAppData(['practitioner' => 'Dr Passé', 'specialty' => '', 'sector' => '', 'address' => '', 'refundLabel' => '']);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $this->client->request('GET', sprintf('/admin/notifications/%d/modifier', (int) $media->getId()));
        $this->client->submitForm('Enregistrer', [
            'media[title]' => 'Nouveau titre',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'Contenu modifié',
            'media[appData][practitioner]' => 'Dr Futur',
            'media[published]' => false,
        ]);

        self::assertResponseRedirects('/admin/notifications');

        $updated = $this->mediaRepository->findOneBy(['title' => 'Nouveau titre']);
        self::assertNotNull($updated);
        self::assertSame(AppKind::DOCTOLIB, $updated->getAppKind());
        self::assertSame('Dr Futur', $updated->getAppData()['practitioner']);
        self::assertFalse($updated->isPublished());
    }

    public function testMoveDown(): void
    {
        $this->mediaFactory->createFeed(3);

        $crawler = $this->client->request('GET', '/admin/notifications');
        $this->client->submit($crawler->filter('.list-group-item')->eq(0)->filter('form')->eq(1)->form());

        self::assertResponseRedirects();
        self::assertSame(['Notification 2', 'Notification 1', 'Notification 3'], $this->titlesInOrder());
    }

    public function testMoveRequiresValidCsrfToken(): void
    {
        $medias = $this->mediaFactory->createFeed(2);

        $this->client->request('POST', sprintf('/admin/notifications/%d/deplacer', (int) $medias[0]->getId()), [
            '_token' => 'jeton-invalide',
            'direction' => 'down',
        ]);

        // Un jeton invalide invalide la session : le firewall renvoie vers la
        // connexion plutôt que de servir un 403.
        self::assertResponseRedirects();
        self::assertSame(['Notification 1', 'Notification 2'], $this->titlesInOrder(), 'L\'ordre est inchangé.');
    }

    public function testDeleteClosesTheGap(): void
    {
        $this->mediaFactory->createFeed(3);

        $crawler = $this->client->request('GET', '/admin/notifications');
        $this->client->submit($crawler->filter('.list-group-item')->eq(0)->selectButton('Supprimer')->form());

        self::assertResponseRedirects();
        self::assertSame([0, 1], array_map(static fn (Media $m): int => $m->getPosition(), $this->mediaRepository->findAllOrdered()));
    }

    public function testCreationPageEmbedsTheLivePreview(): void
    {
        $crawler = $this->client->request('GET', '/admin/notifications/nouveau/instagram');

        self::assertResponseIsSuccessful();
        $panel = $crawler->filter('[data-controller="live-preview"]');
        self::assertSame('/admin/notifications/nouveau/instagram/apercu', $panel->attr('data-live-preview-url-value'));
        self::assertSelectorExists('[data-controller="live-preview"] iframe[data-live-preview-target="frame"]');
        self::assertSelectorExists('form[data-live-preview-target="form"]');
    }

    public function testEditPageEmbedsTheLivePreviewOfTheNotification(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'À retoucher');

        $crawler = $this->client->request('GET', sprintf('/admin/notifications/%d/modifier', (int) $media->getId()));

        self::assertResponseIsSuccessful();
        self::assertSame(
            sprintf('/admin/notifications/%d/apercu', (int) $media->getId()),
            $crawler->filter('[data-controller="live-preview"]')->attr('data-live-preview-url-value'),
        );
    }

    public function testLivePreviewRendersTheDraftWithoutSavingIt(): void
    {
        $this->client->request('POST', '/admin/notifications/nouveau/tinder/apercu', [
            'media' => [
                'title' => 'Brouillon jamais enregistré',
                'description' => 'Une bio écrite à la volée',
                'type' => MediaType::IMAGE->value,
                'auraPoints' => -500,
                'appData' => ['matchName' => 'La coupe de 2015', 'dramaLevel' => 87],
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'La coupe de 2015');
        self::assertSelectorTextContains('.td-body', 'Une bio écrite à la volée');
        self::assertSelectorTextContains('.td-drama', '87 %');
        self::assertSelectorExists('.td-photo img', 'Le brouillon n\'a pas de fichier : l\'emplacement est tout de même rendu pour que le navigateur y mette le sien.');
        self::assertSelectorNotExists('.f-preview-bar', 'Dans le panneau de l\'admin, la barre d\'aperçu est de trop.');
        self::assertSelectorExists('base[target="_top"]');
        self::assertCount(0, $this->mediaRepository->findAll());
    }

    public function testLivePreviewOfAnExistingNotificationLeavesItUntouched(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'Titre enregistré', AppKind::UBER_EATS);

        $this->client->request('POST', sprintf('/admin/notifications/%d/apercu', (int) $media->getId()), [
            'media' => [
                'title' => 'Titre en cours de frappe',
                'type' => MediaType::TEXT->value,
                'textContent' => 'Pas encore validé',
                'auraPoints' => 250,
                'appData' => ['courier' => 'Dodo du futur', 'stars' => 2],
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.ue-hero h1', 'Titre en cours de frappe');
        self::assertSelectorTextContains('.ue-hero p', 'Dodo du futur');
        self::assertSelectorTextContains('.ue-row-total', '+250 aura');

        $reloaded = $this->mediaRepository->find($media->getId());
        self::assertNotNull($reloaded);
        self::assertSame('Titre enregistré', $reloaded->getTitle());
        self::assertSame(100, $reloaded->getAuraPoints());
    }

    public function testLivePreviewToleratesAnIncompleteForm(): void
    {
        $this->client->request('POST', '/admin/notifications/nouveau/doctolib/apercu', [
            'media' => ['title' => '', 'type' => MediaType::TEXT->value, 'auraPoints' => ''],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Rendez-vous honoré');
    }

    public function testEmptiedTitleAndAuraAreRejectedRatherThanCrashing(): void
    {
        $this->client->request('GET', '/admin/notifications/nouveau/uber_eats');
        $this->client->submitForm('Ajouter au fil', [
            'media[title]' => '',
            'media[type]' => MediaType::TEXT->value,
            'media[textContent]' => 'Sans titre',
            'media[auraPoints]' => '',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertSelectorExists('#media_title.is-invalid');
        self::assertCount(0, $this->mediaRepository->findAll());
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
        $this->mediaStorage()->delete((string) $media->getFilePath());
    }

    private function mediaStorage(): FilesystemOperator
    {
        return self::getContainer()->get('media.storage');
    }

    /**
     * @return list<string>
     */
    private function titlesInOrder(): array
    {
        return array_map(static fn (Media $m): string => $m->getTitle(), $this->mediaRepository->findAllOrdered());
    }
}
