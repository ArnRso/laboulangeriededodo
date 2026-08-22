<?php

namespace App\Tests\Integration\Service;

use App\Entity\User;
use App\Service\PasswordChanger;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordChangerTest extends KernelTestCase
{
    private PasswordChanger $passwordChanger;
    private UserFactory $userFactory;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->passwordChanger = $container->get(PasswordChanger::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);
        $this->userFactory = new UserFactory(
            $container->get(EntityManagerInterface::class),
            $this->passwordHasher,
        );
    }

    public function testChangeReplacesThePassword(): void
    {
        $user = $this->userFactory->createAdmin('admin@example.com', 'ancien-mot-de-passe');

        $this->passwordChanger->change($user, 'ancien-mot-de-passe', 'nouveau-mot-de-passe');

        self::assertTrue($this->passwordHasher->isPasswordValid($user, 'nouveau-mot-de-passe'));
        self::assertFalse(
            $this->passwordHasher->isPasswordValid($user, 'ancien-mot-de-passe'),
            'L\'ancien mot de passe ne doit plus fonctionner.',
        );
    }

    public function testChangeIsRefusedWithAWrongCurrentPassword(): void
    {
        $user = $this->userFactory->createAdmin('admin@example.com', 'ancien-mot-de-passe');

        try {
            $this->passwordChanger->change($user, 'mauvais-mot-de-passe', 'nouveau-mot-de-passe');
            self::fail('Une exception était attendue.');
        } catch (\LogicException) {
            // Attendu.
        }

        self::assertTrue(
            $this->passwordHasher->isPasswordValid($user, 'ancien-mot-de-passe'),
            'Le mot de passe reste inchangé.',
        );
    }

    public function testCurrentPasswordValidation(): void
    {
        $user = $this->userFactory->createAdmin('admin@example.com', 'le-bon-mot-de-passe');

        self::assertTrue($this->passwordChanger->isCurrentPasswordValid($user, 'le-bon-mot-de-passe'));
        self::assertFalse($this->passwordChanger->isCurrentPasswordValid($user, 'un-autre'));
    }

    public function testAccountWithoutPasswordCannotBeValidated(): void
    {
        $user = new User();
        $user->setEmail('invite@example.com')->setRoles([User::ROLE_ADMIN]);

        self::assertFalse(
            $this->passwordChanger->isCurrentPasswordValid($user, 'peu-importe'),
            'Un compte en attente d\'invitation n\'a pas de mot de passe à confronter.',
        );
    }

    public function testChangingToTheSamePasswordIsAllowed(): void
    {
        $user = $this->userFactory->createAdmin('admin@example.com', 'meme-mot-de-passe');

        $this->passwordChanger->change($user, 'meme-mot-de-passe', 'meme-mot-de-passe');

        self::assertTrue($this->passwordHasher->isPasswordValid($user, 'meme-mot-de-passe'));
    }
}
