<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Enum\AppKind;
use App\Enum\MediaType;
use App\Repository\MediaRepository;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Une requête malformée — formulaire tronqué, champ absent, valeur d'un
 * autre type — doit être refusée, jamais faire tomber la page en erreur.
 */
class HostileInputTest extends WebTestCase
{
    private KernelBrowser $client;
    private MediaFactory $mediaFactory;
    private MediaRepository $mediaRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->catchExceptions(false);

        $container = self::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->mediaFactory = new MediaFactory($entityManager);
        $this->mediaRepository = $container->get(MediaRepository::class);

        $userFactory = new UserFactory($entityManager, $container->get(UserPasswordHasherInterface::class));
        $this->client->loginUser($userFactory->createAdmin());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function hostilePayloads(): iterable
    {
        yield 'requête vide' => [[]];
        yield 'champs vidés' => [['title' => '', 'description' => '', 'type' => 'text', 'textContent' => '', 'url' => '', 'delayMinutes' => ['hours' => '', 'minutes' => '']]];
        yield 'type inconnu' => [['title' => 'x', 'type' => 'myspace']];
        yield 'titre en tableau' => [['title' => ['tableau'], 'type' => 'text']];
        yield 'titre trop long' => [['title' => str_repeat('a', 5000), 'type' => 'text', 'textContent' => 'y']];
        yield 'délai non numérique' => [['title' => 'x', 'type' => 'text', 'textContent' => 'y', 'delayMinutes' => ['hours' => 'abc', 'minutes' => '-9']]];
        yield 'délai en chaîne' => [['title' => 'x', 'type' => 'text', 'delayMinutes' => 'pas un tableau']];
        yield 'délai démesuré' => [['title' => 'x', 'type' => 'text', 'textContent' => 'y', 'delayMinutes' => ['hours' => '999999999999', 'minutes' => '99']]];
        yield 'publication bizarre' => [['title' => 'x', 'type' => 'text', 'textContent' => 'y', 'published' => 'peut-être']];
        yield 'fragments plats' => [['title' => 'x', 'type' => 'text', 'textContent' => 'y', 'fragments' => ['pas un tableau']]];
        yield 'fragments imbriqués' => [['title' => 'x', 'type' => 'text', 'textContent' => 'y', 'fragments' => [['label' => ['a'], 'text' => ['b']]]]];
        yield 'url invalide' => [['title' => 'x', 'type' => 'link', 'url' => 'pas une url']];
        yield 'détails en chaîne' => [['title' => 'x', 'type' => 'text', 'textContent' => 'y', 'appData' => 'pas un tableau']];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('hostilePayloads')]
    public function testTheDraftFormNeverBreaksOnAMalformedRequest(array $payload): void
    {
        $this->client->request('POST', '/admin/notifications/brouillon', ['media' => $payload], [
            'media' => ['file' => new UploadedFile('', '', null, \UPLOAD_ERR_NO_FILE, true)],
        ]);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('hostilePayloads')]
    public function testTheEditFormNeverBreaksOnAMalformedRequest(array $payload): void
    {
        $media = $this->mediaFactory->createNotification(0, 'Intacte', AppKind::INSTAGRAM);

        $this->client->request('POST', sprintf('/admin/notifications/%d/modifier', (int) $media->getId()), ['media' => $payload], [
            'media' => ['file' => new UploadedFile('', '', null, \UPLOAD_ERR_NO_FILE, true)],
        ]);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function hostileDressPayloads(): iterable
    {
        yield 'requête vide' => [[]];
        yield 'champs en chaîne' => [['fields' => 'x']];
        yield 'source inconnue' => [['fields' => ['username' => ['source' => 'fragment:99']]]];
        yield 'source en tableau' => [['fields' => ['username' => ['source' => ['a', 'b']]]]];
        yield 'sources en chaîne' => [['fields' => ['comments' => ['sources' => 'title']]]];
        yield 'champ inconnu' => [['fields' => ['nexistepas' => ['source' => 'title']]]];
        yield 'texte libre en tableau' => [['fields' => ['username' => ['custom' => ['x']]]]];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[DataProvider('hostileDressPayloads')]
    public function testTheDressingFormNeverBreaksOnAMalformedRequest(array $payload): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'À habiller');

        $this->client->request('POST', sprintf('/admin/notifications/%d/habiller/instagram', (int) $draft->getId()), ['dress' => $payload]);
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());

        $this->client->request('POST', sprintf('/admin/notifications/%d/habiller/instagram/apercu', (int) $draft->getId()), ['dress' => $payload]);
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), 'L\'aperçu en direct reçoit des requêtes à chaque frappe : lui non plus ne doit pas tomber.');
    }

    public function testATruncatedRequestDoesNotCreateAnything(): void
    {
        $this->client->request('POST', '/admin/notifications/brouillon', ['media' => []]);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertCount(0, $this->mediaRepository->findAll());
    }

    public function testAMalformedRequestLeavesTheNotificationUntouched(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'Intacte', AppKind::INSTAGRAM, delayMinutes: 90);

        $this->client->request('POST', sprintf('/admin/notifications/%d/modifier', (int) $media->getId()), [
            'media' => ['title' => str_repeat('a', 5000), 'type' => 'text', 'delayMinutes' => ['hours' => '999999999999', 'minutes' => '0']],
        ]);

        self::getContainer()->get(EntityManagerInterface::class)->clear();
        $reloaded = $this->mediaRepository->find((int) $media->getId());
        self::assertNotNull($reloaded);
        self::assertSame('Intacte', $reloaded->getTitle());
        self::assertSame(90, $reloaded->getDelayMinutes());
        self::assertSame(MediaType::TEXT, $reloaded->getType());
    }
}
