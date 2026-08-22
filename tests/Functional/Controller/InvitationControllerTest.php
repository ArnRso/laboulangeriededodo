<?php

namespace App\Tests\Functional\Controller;

use App\Repository\UserRepository;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class InvitationControllerTest extends WebTestCase
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

    public function testValidTokenShowsPasswordForm(): void
    {
        $this->userFactory->createInvited('invite@example.com', 'token-valide');

        $this->client->request('GET', '/invitation/token-valide');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'invite@example.com');
    }

    public function testPasswordFieldsOfferAVisibilityToggle(): void
    {
        $this->userFactory->createInvited('invite@example.com', 'token-valide');

        $crawler = $this->client->request('GET', '/invitation/token-valide');

        self::assertCount(
            2,
            $crawler->filter('[data-controller="password-visibility"]'),
            'Les deux champs mot de passe ont leur bouton d\'affichage.',
        );
        self::assertCount(2, $crawler->filter('[data-password-visibility-target="input"]'));
    }

    public function testUnknownTokenIsRejected(): void
    {
        $this->client->request('GET', '/invitation/token-inexistant');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testExpiredTokenIsRejected(): void
    {
        $this->userFactory->createInvited(
            'invite@example.com',
            'token-expire',
            new \DateTimeImmutable('-1 day'),
        );

        $this->client->request('GET', '/invitation/token-expire');

        self::assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testSubmittingPasswordActivatesAccount(): void
    {
        $this->userFactory->createInvited('invite@example.com', 'token-valide');

        $this->client->request('GET', '/invitation/token-valide');
        $this->client->submitForm('Enregistrer', [
            'define_password[plainPassword][first]' => 'un-mot-de-passe-solide',
            'define_password[plainPassword][second]' => 'un-mot-de-passe-solide',
        ]);

        self::assertResponseRedirects('/connexion');

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('invite@example.com');
        self::assertNotNull($user);
        self::assertNotNull($user->getPassword(), 'Le mot de passe doit être enregistré.');
        self::assertNull($user->getInvitationToken(), 'Le token doit être consommé.');
    }

    public function testTokenCannotBeReusedAfterCompletion(): void
    {
        $this->userFactory->createInvited('invite@example.com', 'token-valide');

        $this->client->request('GET', '/invitation/token-valide');
        $this->client->submitForm('Enregistrer', [
            'define_password[plainPassword][first]' => 'un-mot-de-passe-solide',
            'define_password[plainPassword][second]' => 'un-mot-de-passe-solide',
        ]);

        $this->client->request('GET', '/invitation/token-valide');

        self::assertResponseStatusCodeSame(
            Response::HTTP_NOT_FOUND,
            'Un lien déjà utilisé ne doit plus fonctionner.',
        );
    }

    public function testMismatchedPasswordsAreRejected(): void
    {
        $this->userFactory->createInvited('invite@example.com', 'token-valide');

        $this->client->request('GET', '/invitation/token-valide');
        $this->client->submitForm('Enregistrer', [
            'define_password[plainPassword][first]' => 'un-mot-de-passe-solide',
            'define_password[plainPassword][second]' => 'un-autre-mot-de-passe',
        ]);

        // Symfony répond 422 lorsqu'un formulaire est soumis invalide.
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('invite@example.com');
        self::assertNotNull($user);
        self::assertNull($user->getPassword(), 'Aucun mot de passe ne doit être enregistré.');
    }

    public function testTooShortPasswordIsRejected(): void
    {
        $this->userFactory->createInvited('invite@example.com', 'token-valide');

        $this->client->request('GET', '/invitation/token-valide');
        $this->client->submitForm('Enregistrer', [
            'define_password[plainPassword][first]' => 'court',
            'define_password[plainPassword][second]' => 'court',
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('invite@example.com');
        self::assertNotNull($user);
        self::assertNull($user->getPassword());
    }

    public function testPasswordAtTheMinimumLengthIsAccepted(): void
    {
        $this->userFactory->createInvited('invite@example.com', 'token-valide');

        $this->client->request('GET', '/invitation/token-valide');
        $this->client->submitForm('Enregistrer', [
            'define_password[plainPassword][first]' => 'sixcar',
            'define_password[plainPassword][second]' => 'sixcar',
        ]);

        self::assertResponseRedirects('/connexion');

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('invite@example.com');
        self::assertNotNull($user);
        self::assertNotNull($user->getPassword(), 'Six caractères suffisent.');
    }

    public function testCompletedAccountCanLogIn(): void
    {
        $this->userFactory->createInvited('invite@example.com', 'token-valide');

        $this->client->request('GET', '/invitation/token-valide');
        $this->client->submitForm('Enregistrer', [
            'define_password[plainPassword][first]' => 'un-mot-de-passe-solide',
            'define_password[plainPassword][second]' => 'un-mot-de-passe-solide',
        ]);

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'invite@example.com',
            '_password' => 'un-mot-de-passe-solide',
        ]);

        self::assertResponseRedirects('/');
    }
}
