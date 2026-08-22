<?php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\InvitationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class InvitationServiceTest extends TestCase
{
    private const NOW = '2026-01-01 12:00:00';

    public function testInviteCreatesUserWithTokenAndNoPassword(): void
    {
        $service = $this->createService();

        $user = $service->invite('dorian@example.com', [User::ROLE_RECIPIENT]);

        self::assertSame('dorian@example.com', $user->getEmail());
        self::assertContains(User::ROLE_RECIPIENT, $user->getRoles());
        self::assertNull($user->getPassword(), 'Le mot de passe est défini par l\'invité lui-même.');
        self::assertNotNull($user->getInvitationToken());
    }

    public function testInviteRejectsDuplicateEmail(): void
    {
        $existing = new User();
        $existing->setEmail('dorian@example.com');

        $service = $this->createService(existingUser: $existing);

        $this->expectException(\InvalidArgumentException::class);
        $service->invite('dorian@example.com', [User::ROLE_RECIPIENT]);
    }

    public function testTokenExpiresAfterConfiguredLifetime(): void
    {
        $service = $this->createService();

        $user = $service->invite('dorian@example.com', [User::ROLE_RECIPIENT]);

        self::assertEquals(
            new \DateTimeImmutable('2026-01-08 12:00:00'),
            $user->getInvitationExpiresAt(),
            'Le token doit expirer 7 jours après son émission.',
        );
    }

    public function testTokensAreUnpredictable(): void
    {
        $service = $this->createService();
        $user = new User();

        $first = $service->refreshInvitationToken($user);
        $second = $service->refreshInvitationToken($user);

        self::assertNotSame($first, $second, 'Deux tokens successifs doivent différer.');
        self::assertSame(64, \strlen($first), 'Un token de 32 octets fait 64 caractères hexadécimaux.');
    }

    public function testRefreshInvalidatesPreviousToken(): void
    {
        $service = $this->createService();
        $user = new User();

        $first = $service->refreshInvitationToken($user);
        $service->refreshInvitationToken($user);

        self::assertNotSame($first, $user->getInvitationToken());
    }

    public function testCompleteInvitationConsumesToken(): void
    {
        $service = $this->createService();
        $user = new User();
        $user->setEmail('dorian@example.com');
        $service->refreshInvitationToken($user);

        $service->completeInvitation($user, 'un-mot-de-passe');

        self::assertNotNull($user->getPassword());
        self::assertNull($user->getInvitationToken(), 'Le lien ne doit plus pouvoir resservir.');
        self::assertNull($user->getInvitationExpiresAt());
    }

    public function testFindUserByTokenReturnsNullWhenExpired(): void
    {
        $user = new User();
        $user->setEmail('dorian@example.com')
            ->setInvitationToken('un-token')
            ->setInvitationExpiresAt(new \DateTimeImmutable('2025-12-31 12:00:00'));

        $service = $this->createService(userByToken: $user);

        self::assertNull($service->findUserByToken('un-token'), 'Un token expiré est refusé.');
    }

    public function testFindUserByTokenReturnsUserWhenValid(): void
    {
        $user = new User();
        $user->setEmail('dorian@example.com')
            ->setInvitationToken('un-token')
            ->setInvitationExpiresAt(new \DateTimeImmutable('2026-01-05 12:00:00'));

        $service = $this->createService(userByToken: $user);

        self::assertSame($user, $service->findUserByToken('un-token'));
    }

    public function testFindUserByTokenReturnsNullForUnknownToken(): void
    {
        self::assertNull($this->createService()->findUserByToken('inconnu'));
    }

    public function testHasPendingInvitationReflectsTokenState(): void
    {
        $service = $this->createService();
        $user = new User();

        self::assertFalse($service->hasPendingInvitation($user), 'Sans token, aucune invitation en attente.');

        $service->refreshInvitationToken($user);
        self::assertTrue($service->hasPendingInvitation($user));

        $user->setInvitationExpiresAt(new \DateTimeImmutable('2025-01-01 12:00:00'));
        self::assertFalse($service->hasPendingInvitation($user), 'Une invitation expirée n\'est plus en attente.');
    }

    private function createService(?User $existingUser = null, ?User $userByToken = null): InvitationService
    {
        $userRepository = self::createStub(UserRepository::class);
        $userRepository->method('findOneByEmail')->willReturn($existingUser);
        $userRepository->method('findOneBy')->willReturn($userByToken);

        $hasher = self::createStub(UserPasswordHasherInterface::class);
        $hasher->method('hashPassword')->willReturn('$2y$hash');

        return new InvitationService(
            $userRepository,
            self::createStub(EntityManagerInterface::class),
            $hasher,
            new MockClock(self::NOW),
        );
    }
}
