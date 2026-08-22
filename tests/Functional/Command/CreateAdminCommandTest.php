<?php

namespace App\Tests\Functional\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();

        $application = new Application($kernel);
        $this->commandTester = new CommandTester($application->find('app:create-admin'));

        $this->userFactory = new UserFactory(
            self::getContainer()->get(EntityManagerInterface::class),
            self::getContainer()->get(UserPasswordHasherInterface::class),
        );
    }

    public function testCreatesAdminWithPendingInvitation(): void
    {
        $exitCode = $this->commandTester->execute(['email' => 'nouvel-admin@example.com']);

        self::assertSame(Command::SUCCESS, $exitCode);

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('nouvel-admin@example.com');

        self::assertNotNull($user);
        self::assertContains(User::ROLE_ADMIN, $user->getRoles());
        self::assertNull($user->getPassword(), 'L\'admin choisit son mot de passe via l\'invitation.');
        self::assertNotNull($user->getInvitationToken());
    }

    public function testSendsInvitationEmail(): void
    {
        $this->commandTester->execute(['email' => 'nouvel-admin@example.com']);

        $email = $this->getSentEmail();

        self::assertSame('Votre accès à l\'administration', $email->getSubject());
        self::assertSame('nouvel-admin@example.com', $email->getTo()[0]->getAddress());
    }

    public function testExactlyOneRecipientIsMailed(): void
    {
        $this->commandTester->execute(['email' => 'nouvel-admin@example.com']);

        $recipients = [];

        foreach (self::getContainer()->get('mailer.message_logger_listener')->getEvents()->getMessages() as $message) {
            self::assertInstanceOf(Email::class, $message);

            foreach ($message->getTo() as $address) {
                $recipients[] = $address->getAddress();
            }
        }

        self::assertSame(['nouvel-admin@example.com'], array_values(array_unique($recipients)));
    }

    public function testInvitationEmailCarriesBothHtmlAndTextParts(): void
    {
        $this->commandTester->execute(['email' => 'nouvel-admin@example.com']);

        $email = $this->getSentEmail();

        self::assertNotNull($email->getHtmlBody());
        self::assertNotNull($email->getTextBody(), 'Un message sans version texte est pénalisé par les filtres.');
        self::assertStringContainsString('mot de passe', (string) $email->getTextBody());
    }

    public function testInvitationEmailCarriesAReplyToAddress(): void
    {
        $this->commandTester->execute(['email' => 'nouvel-admin@example.com']);

        $replyTo = $this->getSentEmail()->getReplyTo();

        self::assertCount(1, $replyTo);
        self::assertSame('laboulangeriededodo@home-arnrso.com', $replyTo[0]->getAddress());
    }

    public function testInvitationEmailContainsWorkingLink(): void
    {
        $this->commandTester->execute(['email' => 'nouvel-admin@example.com']);

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('nouvel-admin@example.com');
        self::assertNotNull($user);

        self::assertStringContainsString(
            (string) $user->getInvitationToken(),
            (string) $this->getSentEmail()->getHtmlBody(),
            'Le mail doit porter le lien d\'invitation.',
        );
    }

    /**
     * Le collecteur enregistre l'email deux fois en envoi synchrone (passage par
     * le bus puis envoi direct) : on vérifie donc le contenu, pas le nombre.
     */
    private function getSentEmail(): Email
    {
        $messages = self::getContainer()->get('mailer.message_logger_listener')->getEvents()->getMessages();

        self::assertNotEmpty($messages, 'Aucun email n\'a été envoyé.');

        $email = $messages[0];
        self::assertInstanceOf(Email::class, $email);

        return $email;
    }

    public function testRejectsInvalidEmail(): void
    {
        $exitCode = $this->commandTester->execute(['email' => 'pas-un-email']);

        self::assertSame(Command::INVALID, $exitCode);
        self::assertStringContainsString('adresse email valide', $this->commandTester->getDisplay());
    }

    public function testRejectsDuplicateEmail(): void
    {
        $this->userFactory->createAdmin('deja@example.com');

        $exitCode = $this->commandTester->execute(['email' => 'deja@example.com']);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('existe déjà', $this->commandTester->getDisplay());
    }
}
