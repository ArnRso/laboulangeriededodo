<?php

namespace App\Tests\Integration\Service;

use App\Entity\MediaAccess;
use App\Repository\MediaAccessRepository;
use App\Service\FeedService;
use App\Service\FeedUnlockService;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class FeedServiceTest extends KernelTestCase
{
    private FeedService $feedService;
    private MediaFactory $mediaFactory;
    private UserFactory $userFactory;
    private MediaAccessRepository $accessRepository;
    private FeedUnlockService $unlockService;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->feedService = $container->get(FeedService::class);
        $this->accessRepository = $container->get(MediaAccessRepository::class);
        $this->unlockService = $container->get(FeedUnlockService::class);
        $this->mediaFactory = new MediaFactory($this->entityManager);
        $this->userFactory = new UserFactory($this->entityManager, $container->get(UserPasswordHasherInterface::class));
    }

    public function testOpeningRecordsTheAccess(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(2);

        $access = $this->feedService->open($user, $medias[0]);

        self::assertSame($medias[0], $access->getMedia());
        self::assertNotNull($this->accessRepository->findOneByUserAndMedia($user, $medias[0]));
    }

    public function testReopeningIsIdempotent(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(2);

        $first = $this->feedService->open($user, $medias[0]);
        $second = $this->feedService->open($user, $medias[0]);

        self::assertSame($first->getId(), $second->getId(), 'Aucun doublon, et le chrono ne repart pas.');
        self::assertEquals($first->getOpenedAt(), $second->getOpenedAt());
    }

    public function testOpeningALockedNotificationIsRefused(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(3);

        $this->feedService->open($user, $medias[0]);

        $this->expectException(\LogicException::class);
        $this->feedService->open($user, $medias[1]);
    }

    public function testSkippingAheadIsRefused(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(3);

        $this->expectException(\LogicException::class);
        $this->feedService->open($user, $medias[2]);
    }

    public function testNextOpensOnceTheDelayHasElapsed(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(3);

        $access = $this->feedService->open($user, $medias[0]);
        $access->setOpenedAt(new \DateTimeImmutable('-25 hours'));
        $this->entityManager->flush();

        $second = $this->feedService->open($user, $medias[1]);

        self::assertSame($medias[1], $second->getMedia());
    }

    public function testOverviewSplitsFreshNextAndSeen(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(4);

        $access = $this->feedService->open($user, $medias[0]);
        $access->setOpenedAt(new \DateTimeImmutable('-25 hours'));
        $this->entityManager->flush();

        $overview = $this->feedService->getOverview($user);

        self::assertNotNull($overview->fresh);
        self::assertSame($medias[1], $overview->fresh->media, 'La deuxième est arrivée.');
        self::assertNotNull($overview->next);
        self::assertSame($medias[2], $overview->next->media, 'La troisième attend.');
        self::assertCount(1, $overview->seen);
        self::assertSame($medias[0], $overview->seen[0]->media);
    }

    public function testOverviewListsSeenMostRecentFirst(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(3, delayMinutes: 0);

        $this->feedService->open($user, $medias[0]);
        $this->feedService->open($user, $medias[1]);
        $this->feedService->open($user, $medias[2]);

        $titles = array_map(
            static fn ($state): string => $state->media->getTitle(),
            $this->feedService->getOverview($user)->seen,
        );

        self::assertSame(['Notification 3', 'Notification 2', 'Notification 1'], $titles);
        self::assertTrue($this->feedService->getOverview($user)->isFinished());
    }

    public function testOverviewOfAnEmptyFeed(): void
    {
        $user = $this->userFactory->createRecipient();

        self::assertTrue($this->feedService->getOverview($user)->isEmpty());
    }

    public function testAccessesOfAnotherUserDoNotLeak(): void
    {
        $dorian = $this->userFactory->createRecipient('dorian@example.com');
        $other = $this->userFactory->createRecipient('autre@example.com');
        $medias = $this->mediaFactory->createFeed(2);

        $access = new MediaAccess();
        $access->setUser($other)->setMedia($medias[0]);
        $this->entityManager->persist($access);
        $this->entityManager->flush();

        $overview = $this->feedService->getOverview($dorian);

        self::assertNotNull($overview->fresh);
        self::assertSame($medias[0], $overview->fresh->media, 'Pour Dorian, la première n\'est pas ouverte.');
        self::assertSame([], $overview->seen);
    }

    public function testSkippingTheWaitMakesTheNextNotificationAvailable(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(2, delayMinutes: 1440);
        $this->feedService->open($user, $medias[0]);

        self::assertFalse($this->unlockService->canOpen($user, $medias[1]), 'Elle attend encore son délai.');

        $skipped = $this->feedService->skipWait($user);

        self::assertSame($medias[1], $skipped);
        self::assertTrue($this->unlockService->canOpen($user, $medias[1]));
    }

    public function testSkippingDoesNotUnlockTheWholeFeed(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(3, delayMinutes: 1440);
        $this->feedService->open($user, $medias[0]);
        $this->feedService->skipWait($user);

        self::assertFalse(
            $this->unlockService->canOpen($user, $medias[2]),
            'Le coup de pouce ne vaut que pour la notification suivante.',
        );
    }

    public function testSkippingTwiceWalksDownTheFeed(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(3, delayMinutes: 1440);
        $this->feedService->open($user, $medias[0]);

        $this->feedService->skipWait($user);
        $this->feedService->open($user, $medias[1]);
        $second = $this->feedService->skipWait($user);

        self::assertSame($medias[2], $second);
        self::assertTrue($this->unlockService->canOpen($user, $medias[2]));
    }

    public function testSkippingWithNothingWaitingIsRefused(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(1, delayMinutes: 1440);
        $this->feedService->open($user, $medias[0]);

        $this->expectException(\LogicException::class);

        $this->feedService->skipWait($user);
    }

    public function testSkippingTheSameNotificationTwiceIsHarmless(): void
    {
        $user = $this->userFactory->createRecipient();
        $medias = $this->mediaFactory->createFeed(2, delayMinutes: 1440);
        $this->feedService->open($user, $medias[0]);
        $this->feedService->skipWait($user);

        // Deux appuis rapprochés visent la même notification : le second ne
        // doit pas buter sur la contrainte d'unicité.
        $again = $this->feedService->skipWaitFor($user, $medias[1]);

        self::assertSame($medias[1], $again);
    }
}
