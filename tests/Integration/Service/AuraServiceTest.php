<?php

namespace App\Tests\Integration\Service;

use App\Entity\MediaAccess;
use App\Service\AuraService;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuraServiceTest extends KernelTestCase
{
    private AuraService $auraService;
    private MediaFactory $mediaFactory;
    private UserFactory $userFactory;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->auraService = $container->get(AuraService::class);
        $this->mediaFactory = new MediaFactory($this->entityManager);
        $this->userFactory = new UserFactory($this->entityManager, $container->get(UserPasswordHasherInterface::class));
    }

    public function testAuraStartsAtZero(): void
    {
        $user = $this->userFactory->createRecipient();

        self::assertSame(0, $this->auraService->total($user));
        self::assertSame(0, $this->auraService->today($user));
    }

    public function testAuraSumsOpenedNotificationsIncludingLosses(): void
    {
        $user = $this->userFactory->createRecipient();
        $gain = $this->mediaFactory->createNotification(0, 'Gain', auraPoints: 100);
        $loss = $this->mediaFactory->createNotification(1, 'Perte', auraPoints: -500);
        $unopened = $this->mediaFactory->createNotification(2, 'Pas ouverte', auraPoints: 1000);

        $this->record($user, $gain, new \DateTimeImmutable('-3 days'));
        $this->record($user, $loss, new \DateTimeImmutable('-1 hour'));

        self::assertSame(-400, $this->auraService->total($user), 'Une décision catastrophique coûte cher.');
        self::assertSame(-500, $this->auraService->today($user), 'Seule l\'ouverture du jour compte pour aujourd\'hui.');
        self::assertSame(1000, $unopened->getAuraPoints(), 'Le non ouvert ne rapporte rien.');
    }

    public function testAuraOfAnotherUserIsNotCounted(): void
    {
        $dorian = $this->userFactory->createRecipient('dorian@example.com');
        $other = $this->userFactory->createRecipient('autre@example.com');
        $media = $this->mediaFactory->createNotification(0, auraPoints: 300);

        $this->record($other, $media, new \DateTimeImmutable());

        self::assertSame(0, $this->auraService->total($dorian));
    }

    private function record(\App\Entity\User $user, \App\Entity\Media $media, \DateTimeImmutable $openedAt): void
    {
        $access = new MediaAccess();
        $access->setUser($user)->setMedia($media)->setOpenedAt($openedAt);
        $this->entityManager->persist($access);
        $this->entityManager->flush();
    }
}
