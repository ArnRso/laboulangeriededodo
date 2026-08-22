<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
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
        $this->client->request('GET', '/admin/destinataire');
        $this->client->submitForm('Envoyer l\'invitation', [
            'invite_recipient[email]' => 'dorian@example.com',
        ]);

        self::assertResponseRedirects('/admin/destinataire');

        $recipient = $this->userRepository->findOneByEmail('dorian@example.com');
        self::assertNotNull($recipient);
        self::assertContains(User::ROLE_RECIPIENT, $recipient->getRoles());
        self::assertNull($recipient->getPassword(), 'Le destinataire choisit son mot de passe.');
        self::assertNotNull($recipient->getInvitationToken());
    }

    public function testInvitationEmailIsSentToTheRecipient(): void
    {
        $this->client->request('GET', '/admin/destinataire');
        $this->client->submitForm('Envoyer l\'invitation', [
            'invite_recipient[email]' => 'dorian@example.com',
        ]);

        $email = $this->getSentEmail();

        self::assertSame('dorian@example.com', $email->getTo()[0]->getAddress());
        self::assertSame('Une surprise t\'attend', $email->getSubject());
    }

    public function testResendingReplacesThePreviousToken(): void
    {
        $this->client->request('GET', '/admin/destinataire');
        $this->client->submitForm('Envoyer l\'invitation', [
            'invite_recipient[email]' => 'dorian@example.com',
        ]);

        $firstToken = $this->readTokenFromDatabase('dorian@example.com');

        $crawler = $this->client->request('GET', '/admin/destinataire');
        self::assertSelectorTextContains('button[type="submit"]', 'Renvoyer');
        $this->client->submit($crawler->filter('form')->form([
            'invite_recipient[email]' => 'dorian@example.com',
        ]));
        self::assertResponseRedirects();

        $secondToken = $this->readTokenFromDatabase('dorian@example.com');

        self::assertNotSame($firstToken, $secondToken, 'Le renvoi génère un lien neuf.');
    }

    public function testActivatedRecipientCannotBeReinvited(): void
    {
        $this->userFactory->createRecipient('dorian@example.com');

        $this->client->request('GET', '/admin/destinataire');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'a activé son compte');
        self::assertSelectorNotExists('button[type="submit"]');
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->client->request('GET', '/admin/destinataire');
        $this->client->submitForm('Envoyer l\'invitation', [
            'invite_recipient[email]' => 'pas-un-email',
        ]);

        self::assertNull($this->userRepository->findOneByRole(User::ROLE_RECIPIENT));
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
