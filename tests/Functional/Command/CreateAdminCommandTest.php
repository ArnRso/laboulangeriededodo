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

        $messages = self::getContainer()->get('mailer.message_logger_listener')->getEvents()->getMessages();

        self::assertCount(1, $messages);

        $email = $messages[0];
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('Votre accès à l\'administration', $email->getSubject());
        self::assertSame('nouvel-admin@example.com', $email->getTo()[0]->getAddress());
    }

    public function testInvitationEmailContainsWorkingLink(): void
    {
        $this->commandTester->execute(['email' => 'nouvel-admin@example.com']);

        $user = self::getContainer()->get(UserRepository::class)->findOneByEmail('nouvel-admin@example.com');
        self::assertNotNull($user);

        $messages = self::getContainer()->get('mailer.message_logger_listener')->getEvents()->getMessages();
        $email = $messages[0];
        self::assertInstanceOf(Email::class, $email);

        self::assertStringContainsString(
            (string) $user->getInvitationToken(),
            (string) $email->getHtmlBody(),
            'Le mail doit porter le lien d\'invitation.',
        );
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
