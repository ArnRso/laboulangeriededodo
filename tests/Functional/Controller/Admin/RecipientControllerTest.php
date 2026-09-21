<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\User;
use App\Enum\Avatar;
use App\Repository\UserRepository;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RecipientControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserFactory $userFactory;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $this->userFactory = new UserFactory(
            $container->get(EntityManagerInterface::class),
            $container->get(UserPasswordHasherInterface::class),
        );
        $this->userRepository = $container->get(UserRepository::class);

        $this->client->loginUser($this->userFactory->createAdmin());
    }

    public function testInvitesRecipient(): void
    {
        $this->client->request('GET', '/admin/destinataires');
        $this->client->submitForm('Envoyer l\'invitation', [
            'invite_recipient[email]' => 'dorian@example.com',
            'invite_recipient[displayName]' => 'Dodo',
            'invite_recipient[avatar]' => '🦤',
        ]);

        self::assertResponseRedirects('/admin/destinataires');

        $recipient = $this->userRepository->findOneByEmail('dorian@example.com');
        self::assertNotNull($recipient);
        self::assertContains(User::ROLE_RECIPIENT, $recipient->getRoles());
        self::assertNull($recipient->getPassword(), 'Le destinataire choisit son mot de passe.');
        self::assertNotNull($recipient->getInvitationToken());
        self::assertSame('Dodo', $recipient->getDisplayName());
        self::assertSame(Avatar::DODO, $recipient->getAvatar());
    }

    public function testDisplayNameIsRequired(): void
    {
        $this->client->request('GET', '/admin/destinataires');
        $this->client->submitForm('Envoyer l\'invitation', [
            'invite_recipient[email]' => 'dorian@example.com',
            'invite_recipient[displayName]' => '',
            'invite_recipient[avatar]' => '🦤',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertNull($this->userRepository->findOneByEmail('dorian@example.com'));
    }

    public function testInvitationEmailIsSentToTheRecipient(): void
    {
        $this->client->request('GET', '/admin/destinataires');
        $this->client->submitForm('Envoyer l\'invitation', [
            'invite_recipient[email]' => 'dorian@example.com',
            'invite_recipient[displayName]' => 'Dodo',
            'invite_recipient[avatar]' => '🦤',
        ]);

        $email = $this->getSentEmail();

        self::assertSame('dorian@example.com', $email->getTo()[0]->getAddress());
        self::assertSame('Une surprise t\'attend', $email->getSubject());
    }

    public function testResendingReplacesThePreviousToken(): void
    {
        $this->invite('dorian@example.com', 'Dodo');
        $firstToken = $this->readTokenFromDatabase('dorian@example.com');

        $this->invite('dorian@example.com', 'Dodo le retour');
        self::assertResponseRedirects();

        $secondToken = $this->readTokenFromDatabase('dorian@example.com');

        self::assertNotSame($firstToken, $secondToken, 'Le renvoi génère un lien neuf.');
        $recipient = $this->userRepository->findOneByEmail('dorian@example.com');
        self::assertNotNull($recipient);
        self::assertSame('Dodo le retour', $recipient->getDisplayName(), 'Le renvoi corrige aussi le prénom.');
    }

    public function testSeveralRecipientsCanBeInvited(): void
    {
        $this->invite('dorian@example.com', 'Dodo');
        $this->invite('marie@example.com', 'Marie');

        $crawler = $this->client->request('GET', '/admin/destinataires');

        self::assertResponseIsSuccessful();
        self::assertCount(2, $crawler->filter('.list-group-item'));
        self::assertSelectorTextContains('body', 'Dodo');
        self::assertSelectorTextContains('body', 'Marie');
        self::assertCount(2, $this->userRepository->findByRole(User::ROLE_RECIPIENT));
    }

    public function testAnActivatedRecipientIsListedAndCannotBeReinvited(): void
    {
        $this->userFactory->createRecipient('dorian@example.com');

        $this->client->request('GET', '/admin/destinataires');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.list-group-item', 'dorian@example.com');

        $this->invite('dorian@example.com', 'Dodo');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'déjà activé son compte');
    }

    public function testAnAdminAddressIsRefused(): void
    {
        $this->invite('admin@example.com', 'Marie');

        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-danger', 'administrateur');
        self::assertCount(0, $this->userRepository->findByRole(User::ROLE_RECIPIENT));
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->client->request('GET', '/admin/destinataires');
        $this->client->submitForm('Envoyer l\'invitation', [
            'invite_recipient[email]' => 'pas-un-email',
        ]);

        self::assertCount(0, $this->userRepository->findByRole(User::ROLE_RECIPIENT));
    }

    public function testEachRecipientWalksTheSameFeedAtTheirOwnPace(): void
    {
        $dorian = $this->userFactory->createRecipient('dorian@example.com');
        $marie = $this->userFactory->createRecipient('marie@example.com');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $medias = new MediaFactory($entityManager)->createFeed(3, delayMinutes: 0);

        // Dorian en ouvre deux, Marie aucune.
        $this->client->loginUser($dorian);
        foreach ([$medias[0], $medias[1]] as $media) {
            $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $media->getId()));
            self::assertResponseIsSuccessful();
        }

        $this->client->loginUser($marie);
        $crawler = $this->client->request('GET', '/mon-espace');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('.f-n-seen'), 'Marie n\'hérite pas des ouvertures de Dorian.');
        // Le titre est masqué tant que la notification n'est pas ouverte :
        // c'est le lien de la carte qui dit où elle en est.
        self::assertSame(
            sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()),
            $crawler->filter('.f-n-fresh')->attr('href'),
            'Elle commence au début du même fil.',
        );

        $this->client->loginUser($dorian);
        $crawler = $this->client->request('GET', '/mon-espace');
        self::assertCount(2, $crawler->filter('.f-n-seen'), 'Dorian garde les siennes.');
    }

    public function testResettingSendsSomeoneBackToTheStartOfTheFeed(): void
    {
        $dorian = $this->userFactory->createRecipient('dorian@example.com');
        $marie = $this->userFactory->createRecipient('marie@example.com');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $medias = new MediaFactory($entityManager)->createFeed(2, delayMinutes: 0);

        foreach ([$dorian, $marie] as $recipient) {
            $this->client->loginUser($recipient);
            $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));
        }

        $this->client->loginUser($this->userFactory->createAdmin('autre-admin@example.com'));
        $crawler = $this->client->request('GET', '/admin/destinataires');
        $this->client->submit($crawler->filter(sprintf('form[action="/admin/destinataires/%d/reinitialiser"]', (int) $dorian->getId()))->form());

        self::assertResponseRedirects('/admin/destinataires');
        $this->client->followRedirect();
        self::assertSelectorTextContains('.alert-success', 'repart du début');

        $this->client->loginUser($dorian);
        $crawler = $this->client->request('GET', '/mon-espace');
        self::assertCount(0, $crawler->filter('.f-n-seen'), 'Dorian repart de zéro.');

        $this->client->loginUser($marie);
        $crawler = $this->client->request('GET', '/mon-espace');
        self::assertCount(1, $crawler->filter('.f-n-seen'), 'La progression de Marie est intacte.');
    }

    public function testResettingRefusesAForgedToken(): void
    {
        $dorian = $this->userFactory->createRecipient('dorian@example.com');
        $medias = new MediaFactory(self::getContainer()->get(EntityManagerInterface::class))->createFeed(1, delayMinutes: 0);

        $this->client->loginUser($dorian);
        $this->client->request('GET', sprintf('/mon-espace/notifications/%d', (int) $medias[0]->getId()));

        $this->client->loginUser($this->userFactory->createAdmin('autre-admin@example.com'));
        $this->client->request('POST', sprintf('/admin/destinataires/%d/reinitialiser', (int) $dorian->getId()), ['_token' => 'jeton-invalide']);

        // Un jeton invalide invalide la session : le firewall renvoie vers la
        // connexion plutôt que de servir un 403.
        self::assertResponseRedirects();

        $this->client->loginUser($dorian);
        $crawler = $this->client->request('GET', '/mon-espace');
        self::assertCount(1, $crawler->filter('.f-n-seen'), 'Rien n\'a été remis à zéro.');
    }

    public function testTheAvatarPickerIsDrawnOnlyOnce(): void
    {
        $crawler = $this->client->request('GET', '/admin/destinataires');

        self::assertResponseIsSuccessful();
        self::assertCount(\count(Avatar::cases()), $crawler->filter('.avatar-grid .avatar-choice'));
        self::assertCount(
            \count(Avatar::cases()),
            $crawler->filter('input[name="invite_recipient[avatar]"]'),
            'Un radio par avatar : sans setRendered, form_end les redessinerait en liste sous le bouton.',
        );
        self::assertCount(0, $crawler->filter('.form-check'));
    }

    /**
     * @param array<string, string> $extra
     */
    private function invite(string $email, string $displayName, array $extra = []): void
    {
        $this->client->request('GET', '/admin/destinataires');
        $this->client->submitForm('Envoyer l\'invitation', array_merge([
            'invite_recipient[email]' => $email,
            'invite_recipient[displayName]' => $displayName,
            'invite_recipient[avatar]' => '🦤',
        ], $extra));
    }

    /**
     * Le token est relu en SQL : l'identity map de Doctrine servirait l'entité
     * déjà chargée, avec son token d'avant le renvoi.
     */
    private function readTokenFromDatabase(string $email): string
    {
        $token = self::getContainer()->get(EntityManagerInterface::class)
            ->getConnection()
            ->fetchOne('SELECT invitation_token FROM "user" WHERE email = ?', [$email]);

        self::assertIsString($token);

        return $token;
    }

    private function getSentEmail(): Email
    {
        $messages = self::getContainer()->get('mailer.message_logger_listener')->getEvents()->getMessages();

        self::assertNotEmpty($messages, 'Aucun email n\'a été envoyé.');

        $email = $messages[0];
        self::assertInstanceOf(Email::class, $email);

        return $email;
    }
}
