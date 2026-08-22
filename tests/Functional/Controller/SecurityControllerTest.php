<?php

namespace App\Tests\Functional\Controller;

use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->userFactory = new UserFactory(
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->get(UserPasswordHasherInterface::class),
        );
    }

    public function testLoginPageIsPubliclyAccessible(): void
    {
        $this->client->request('GET', '/connexion');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form input[name="_username"]');
    }

    public function testValidCredentialsLogTheUserIn(): void
    {
        $this->userFactory->createAdmin('admin@example.com', 'un-mot-de-passe-valide');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'admin@example.com',
            '_password' => 'un-mot-de-passe-valide',
        ]);

        self::assertResponseRedirects('/');

        // La racine oriente ensuite chacun vers son espace.
        $this->client->followRedirect();
        self::assertResponseRedirects('/admin/packs');
    }

    public function testTheRecipientLandsOnTheirJourney(): void
    {
        $this->userFactory->createRecipient('dorian@example.com', 'un-mot-de-passe-valide');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'dorian@example.com',
            '_password' => 'un-mot-de-passe-valide',
        ]);

        $this->client->followRedirect();
        self::assertResponseRedirects('/mon-espace');
    }

    public function testInvalidPasswordIsRejected(): void
    {
        $this->userFactory->createAdmin('admin@example.com', 'un-mot-de-passe-valide');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'admin@example.com',
            '_password' => 'mauvais-mot-de-passe',
        ]);

        self::assertResponseRedirects('/connexion');
        $this->client->followRedirect();
        self::assertSelectorExists('.alert-danger');
    }

    public function testUnknownEmailIsRejected(): void
    {
        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'inconnu@example.com',
            '_password' => 'peu-importe',
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testInvitedUserWithoutPasswordCannotLogIn(): void
    {
        $this->userFactory->createInvited('invite@example.com');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'invite@example.com',
            '_password' => '',
        ]);

        self::assertResponseRedirects('/connexion');
    }

    public function testAdminAreaRejectsAnonymousVisitors(): void
    {
        $this->client->request('GET', '/admin/packs');

        self::assertResponseRedirects();
        self::assertStringContainsString(
            '/connexion',
            (string) $this->client->getResponse()->headers->get('Location'),
        );
    }

    public function testAdminAreaRejectsTheRecipient(): void
    {
        $this->client->loginUser($this->userFactory->createRecipient());

        $this->client->request('GET', '/admin/packs');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testLogoutRedirectsHome(): void
    {
        $admin = $this->userFactory->createAdmin();
        $this->client->loginUser($admin);

        $this->client->request('GET', '/deconnexion');

        self::assertResponseRedirects('/');
    }
}
