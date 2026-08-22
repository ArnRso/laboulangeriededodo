<?php

namespace App\Tests\Functional\Controller\Admin;

use App\Repository\UserRepository;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccountControllerTest extends WebTestCase
{
    private const CURRENT_PASSWORD = 'mot-de-passe-actuel';

    private KernelBrowser $client;
    private UserFactory $userFactory;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        $container = self::getContainer();
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $this->userFactory = new UserFactory(
            $container->get(EntityManagerInterface::class),
            $this->passwordHasher,
        );
    }

    public function testPageIsReachableByAnAdmin(): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $this->client->request('GET', '/admin/mon-compte');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Changer de mot de passe');
    }

    public function testPageIsRefusedToTheRecipient(): void
    {
        $this->client->loginUser($this->userFactory->createRecipient());

        $this->client->request('GET', '/admin/mon-compte');

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testPageIsRefusedToAnonymousVisitors(): void
    {
        $this->client->request('GET', '/admin/mon-compte');

        self::assertResponseRedirects();
        self::assertStringContainsString(
            '/connexion',
            (string) $this->client->getResponse()->headers->get('Location'),
        );
    }

    public function testPasswordIsChanged(): void
    {
        $admin = $this->userFactory->createAdmin('admin@example.com', self::CURRENT_PASSWORD);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/mon-compte');
        $this->client->submitForm('Enregistrer', [
            'change_password[currentPassword]' => self::CURRENT_PASSWORD,
            'change_password[newPassword][first]' => 'nouveau-mot-de-passe',
            'change_password[newPassword][second]' => 'nouveau-mot-de-passe',
        ]);

        self::assertResponseRedirects('/admin/mon-compte');

        // L'entité est relue : la requête HTTP s'exécute dans un autre contexte Doctrine.
        $updated = self::getContainer()->get(UserRepository::class)->findOneByEmail('admin@example.com');
        self::assertNotNull($updated);
        self::assertTrue($this->passwordHasher->isPasswordValid($updated, 'nouveau-mot-de-passe'));
        self::assertFalse(
            $this->passwordHasher->isPasswordValid($updated, self::CURRENT_PASSWORD),
            'L\'ancien mot de passe ne fonctionne plus.',
        );
    }

    public function testWrongCurrentPasswordIsRefused(): void
    {
        $admin = $this->userFactory->createAdmin('admin@example.com', self::CURRENT_PASSWORD);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/mon-compte');
        $this->client->submitForm('Enregistrer', [
            'change_password[currentPassword]' => 'ce-nest-pas-le-bon',
            'change_password[newPassword][first]' => 'nouveau-mot-de-passe',
            'change_password[newPassword][second]' => 'nouveau-mot-de-passe',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertTrue(
            $this->passwordHasher->isPasswordValid($admin, self::CURRENT_PASSWORD),
            'Sans le mot de passe actuel, rien ne change.',
        );
    }

    public function testMismatchedNewPasswordsAreRefused(): void
    {
        $admin = $this->userFactory->createAdmin('admin@example.com', self::CURRENT_PASSWORD);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/mon-compte');
        $this->client->submitForm('Enregistrer', [
            'change_password[currentPassword]' => self::CURRENT_PASSWORD,
            'change_password[newPassword][first]' => 'nouveau-mot-de-passe',
            'change_password[newPassword][second]' => 'un-autre-mot-de-passe',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertTrue($this->passwordHasher->isPasswordValid($admin, self::CURRENT_PASSWORD));
    }

    public function testTooShortNewPasswordIsRefused(): void
    {
        $admin = $this->userFactory->createAdmin('admin@example.com', self::CURRENT_PASSWORD);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/mon-compte');
        $this->client->submitForm('Enregistrer', [
            'change_password[currentPassword]' => self::CURRENT_PASSWORD,
            'change_password[newPassword][first]' => 'court',
            'change_password[newPassword][second]' => 'court',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertTrue($this->passwordHasher->isPasswordValid($admin, self::CURRENT_PASSWORD));
    }

    public function testTheNewPasswordWorksForLoggingIn(): void
    {
        $this->userFactory->createAdmin('admin@example.com', self::CURRENT_PASSWORD);
        $admin = $this->userFactory->createAdmin('autre@example.com', self::CURRENT_PASSWORD);
        $this->client->loginUser($admin);

        $this->client->request('GET', '/admin/mon-compte');
        $this->client->submitForm('Enregistrer', [
            'change_password[currentPassword]' => self::CURRENT_PASSWORD,
            'change_password[newPassword][first]' => 'nouveau-mot-de-passe',
            'change_password[newPassword][second]' => 'nouveau-mot-de-passe',
        ]);

        $this->client->request('GET', '/deconnexion');

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'autre@example.com',
            '_password' => 'nouveau-mot-de-passe',
        ]);

        self::assertResponseRedirects('/');
    }

    public function testEveryPasswordFieldHasAVisibilityToggle(): void
    {
        $this->client->loginUser($this->userFactory->createAdmin());

        $crawler = $this->client->request('GET', '/admin/mon-compte');

        self::assertCount(
            3,
            $crawler->filter('[data-controller="password-visibility"]'),
            'Mot de passe actuel, nouveau et confirmation.',
        );
    }
}
