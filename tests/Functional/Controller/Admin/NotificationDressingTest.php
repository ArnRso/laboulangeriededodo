<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Form\AppDetails\AppDetailsRegistry;
use App\Repository\MediaRepository;
use App\Service\FeedService;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Habiller une notification : choisir l'app, verser ses textes dans les
 * champs, ou en changer sans rien perdre.
 */
class NotificationDressingTest extends WebTestCase
{
    private KernelBrowser $client;
    private MediaFactory $mediaFactory;
    private UserFactory $userFactory;
    private MediaRepository $mediaRepository;
    private AppDetailsRegistry $registry;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->mediaFactory = new MediaFactory($this->entityManager);
        $this->mediaRepository = $container->get(MediaRepository::class);
        $this->registry = $container->get(AppDetailsRegistry::class);

        $this->userFactory = new UserFactory($this->entityManager, $container->get(UserPasswordHasherInterface::class));
        $this->client->loginUser($this->userFactory->createAdmin());
    }

    public function testChoosingAnAppForADraftLinksToItsDressingForm(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'À habiller');

        $crawler = $this->client->request('GET', sprintf('/admin/notifications/%d/habiller', (int) $draft->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Habiller « À habiller »');
        self::assertCount(\count(AppKind::cases()), $crawler->filter(sprintf('a.card[href^="/admin/notifications/%d/habiller/"]', (int) $draft->getId())));
        self::assertSelectorNotExists('a.card[href="/admin/notifications/brouillon"]', 'Pas de nouveau brouillon depuis l\'habillage.');
    }

    public function testTheDressingFormListsTheAppFieldsAndTheSources(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Le message de 4 h 12', 'Tu avais dit une heure.', [['label' => 'Marie', 'text' => "j'étais là"]]);

        $crawler = $this->client->request('GET', sprintf('/admin/notifications/%d/habiller/instagram', (int) $draft->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select[name="dress[fields][username][source]"]');
        self::assertSelectorExists('select[name="dress[fields][username][source]"] option[value="title"]');
        self::assertSelectorExists('select[name="dress[fields][username][source]"] option[value="description"]');
        self::assertSelectorExists('select[name="dress[fields][username][source]"] option[value="fragment:0"]');
        self::assertSelectorExists('select[name="dress[fields][username][source]"] option[value="custom"]');
        self::assertSelectorExists('input[type="checkbox"][name="dress[fields][comments][sources][]"][value="fragment:0"]', 'Un champ sur plusieurs lignes accepte plusieurs sources.');
        self::assertSelectorExists('textarea[name="dress[fields][comments][custom]"]');
        self::assertSelectorTextContains('body', 'Fragment · Marie');
        self::assertCount(1, $crawler->filter('[data-controller="live-preview"] iframe'));
    }

    public function testDressingComposesTheAppDataAndRedirectsToTheEdition(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Le message de 4 h 12', 'Tu avais dit une heure.', [['label' => 'Marie', 'text' => "marie: j'étais là"]]);

        $this->dress($draft, 'instagram', [
            'caption' => ['sources' => ['title', 'description']],
            'comments' => ['sources' => ['fragment:0']],
            'badge' => ['source' => 'custom', 'custom' => 'Icon · Main character energy'],
        ]);

        self::assertResponseRedirects(sprintf('/admin/notifications/%d/modifier', (int) $draft->getId()));

        $dressed = $this->reload($draft);
        self::assertSame(AppKind::INSTAGRAM, $dressed->getAppKind());
        self::assertFalse($dressed->isPublished());
        self::assertSame("Le message de 4 h 12\nTu avais dit une heure.", $dressed->getAppData()['caption']);
        self::assertSame("marie: j'étais là", $dressed->getAppData()['comments']);
        self::assertSame('Icon · Main character energy', $dressed->getAppData()['badge']);
        self::assertSame('dodo.du.passe', $dressed->getAppData()['username'], 'Un champ non mappé garde le défaut.');
        self::assertSame(array_keys($this->registry->defaultsFor(AppKind::INSTAGRAM)), array_keys($dressed->getAppData()));

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'Habillée en Instagram');
        self::assertSelectorExists('[name="media[appData][caption]"]');
    }

    public function testTheTitleCanBeSentToACommentField(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Un titre qui devient un commentaire');

        $this->dress($draft, 'instagram', ['comments' => ['sources' => ['title']]]);

        self::assertSame('Un titre qui devient un commentaire', $this->reload($draft)->getAppData()['comments']);
    }

    public function testANumberFieldFallsBackToItsDefaultWhenTheSourceIsNotANumber(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Pas un nombre');

        $this->dress($draft, 'instagram', ['likesCount' => ['source' => 'title']]);

        self::assertSame(1240, $this->reload($draft)->getAppData()['likesCount']);
    }

    public function testTheLivePreviewRendersTheMappingWithoutSaving(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Titre en aperçu', null, [['label' => 'Marie', 'text' => "j'étais là"]]);

        $this->client->request('POST', sprintf('/admin/notifications/%d/habiller/instagram/apercu', (int) $draft->getId()), [
            'dress' => ['fields' => ['caption' => ['sources' => ['title']]]],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('body.f-open-ig');
        self::assertSelectorTextContains('.ig-cap', 'Titre en aperçu');

        $untouched = $this->reload($draft);
        self::assertTrue($untouched->isDraft(), 'L\'aperçu n\'habille pas pour de vrai.');
        self::assertCount(1, $untouched->getFragments(), 'L\'aperçu n\'archive rien non plus.');
    }

    public function testChangingTheAppReusesTheCurrentValuesAndArchivesTheRest(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'Le premier jour', AppKind::UBER_EATS);
        $media->setAppData([...$this->registry->defaultsFor(AppKind::UBER_EATS), 'courier' => 'Marie du futur', 'orderTitle' => 'Titre spécial']);
        $media->setPublished(true);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', sprintf('/admin/notifications/%d/habiller/deliveroo', (int) $media->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('select[name="dress[fields][restaurant][source]"] option[value="app:courier"]', 'Uber Eats · Livreur');
        self::assertCount(1, $crawler->filter('select[name="dress[fields][restaurant][source]"] option[value="app:orderTitle"]'));

        $this->dress($media, 'deliveroo', ['restaurant' => ['source' => 'app:courier']]);

        $dressed = $this->reload($media);
        self::assertSame(AppKind::DELIVEROO, $dressed->getAppKind());
        self::assertSame('Marie du futur', $dressed->getAppData()['restaurant']);
        self::assertTrue($dressed->isPublished(), 'Habiller ne touche pas à la publication tant que Dorian n\'a rien ouvert.');
        self::assertSame(
            [['label' => 'Uber Eats · Titre de la commande', 'text' => 'Titre spécial']],
            $dressed->getFragments(),
            'La valeur reprise n\'est pas archivée ; celle qui est abandonnée l\'est ; les défauts jamais.',
        );
    }

    public function testANotificationAlreadyOpenedCannotBeDressedAgain(): void
    {
        $dorian = $this->userFactory->createRecipient();
        $media = $this->mediaFactory->createNotification(0, 'Déjà vue', AppKind::UBER_EATS, delayMinutes: 0);
        self::getContainer()->get(FeedService::class)->open($dorian, $media);

        $this->client->request('GET', sprintf('/admin/notifications/%d/habiller', (int) $media->getId()));
        self::assertResponseRedirects(sprintf('/admin/notifications/%d/modifier', (int) $media->getId()));

        $this->client->request('POST', sprintf('/admin/notifications/%d/habiller/instagram', (int) $media->getId()), [
            'dress' => ['fields' => ['caption' => ['sources' => ['title']]]],
        ]);
        self::assertResponseRedirects(sprintf('/admin/notifications/%d/modifier', (int) $media->getId()));
        self::assertSame(AppKind::UBER_EATS, $this->reload($media)->getAppKind());

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'déjà ouvert');
    }

    public function testAnUnknownSourceIsRejected(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Source inconnue');

        $this->dress($draft, 'instagram', ['username' => ['source' => 'fragment:9']]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertTrue($this->reload($draft)->isDraft());
    }

    public function testAPostWithoutTokenChangesNothing(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Sans jeton');

        $this->client->request('POST', sprintf('/admin/notifications/%d/habiller/instagram', (int) $draft->getId()), [
            'dress' => ['fields' => ['caption' => ['sources' => ['title']]]],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertTrue($this->reload($draft)->isDraft());
    }

    public function testDressingAnUnknownAppIsNotFound(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Myspace');

        $this->client->request('GET', sprintf('/admin/notifications/%d/habiller/myspace', (int) $draft->getId()));

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testThePreviewOfADraftSendsToTheChoiceOfAnApp(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Sans écran');

        $this->client->request('GET', sprintf('/admin/notifications/%d/apercu', (int) $draft->getId()));

        self::assertResponseRedirects(sprintf('/admin/notifications/%d/habiller', (int) $draft->getId()));
    }

    /**
     * Les cases à cocher d'un même tableau sont indexées par position dans le
     * crawler : on poste le mapping tel quel, avec le jeton lu dans la page.
     *
     * @param array<string, array<string, mixed>> $fields
     */
    private function dress(Media $media, string $app, array $fields): void
    {
        $crawler = $this->client->request('GET', sprintf('/admin/notifications/%d/habiller/%s', (int) $media->getId(), $app));
        $token = $crawler->filter('input[name="dress[_token]"]')->attr('value');

        $this->client->request('POST', sprintf('/admin/notifications/%d/habiller/%s', (int) $media->getId(), $app), [
            'dress' => ['_token' => $token, 'fields' => $fields],
        ]);
    }

    private function reload(Media $media): Media
    {
        $this->entityManager->clear();
        $reloaded = $this->mediaRepository->find((int) $media->getId());
        self::assertNotNull($reloaded);

        return $reloaded;
    }
}
