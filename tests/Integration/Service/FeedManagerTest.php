<?php

namespace App\Tests\Integration\Service;

use App\Entity\Media;
use App\Enum\MediaType;
use App\Repository\MediaRepository;
use App\Service\FeedManager;
use App\Tests\Factory\MediaFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FeedManagerTest extends KernelTestCase
{
    private FeedManager $feedManager;
    private MediaFactory $mediaFactory;
    private MediaRepository $mediaRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->feedManager = $container->get(FeedManager::class);
        $this->mediaRepository = $container->get(MediaRepository::class);
        $this->mediaFactory = new MediaFactory($container->get(EntityManagerInterface::class));
    }

    public function testNewNotificationIsAppendedAtTheEnd(): void
    {
        $this->mediaFactory->createFeed(3);

        $media = $this->newMedia('Nouvelle');
        $this->feedManager->add($media);

        self::assertSame(3, $media->getPosition());
    }

    public function testFirstNotificationStartsAtZero(): void
    {
        $media = $this->newMedia('Première');
        $this->feedManager->add($media);

        self::assertSame(0, $media->getPosition());
    }

    public function testDeletingClosesTheGap(): void
    {
        $medias = $this->mediaFactory->createFeed(4);

        $this->feedManager->delete($medias[1]);

        $remaining = $this->mediaRepository->findAllOrdered();

        self::assertSame([0, 1, 2], array_map(static fn (Media $m): int => $m->getPosition(), $remaining));
        self::assertSame(['Notification 1', 'Notification 3', 'Notification 4'], $this->titles());
    }

    public function testMoveDown(): void
    {
        $medias = $this->mediaFactory->createFeed(3);

        $this->feedManager->move($medias[0], 1);

        self::assertSame(['Notification 2', 'Notification 1', 'Notification 3'], $this->titles());
    }

    public function testMoveUp(): void
    {
        $medias = $this->mediaFactory->createFeed(3);

        $this->feedManager->move($medias[2], -1);

        self::assertSame(['Notification 1', 'Notification 3', 'Notification 2'], $this->titles());
    }

    public function testMovingBeyondTheEdgesDoesNothing(): void
    {
        $medias = $this->mediaFactory->createFeed(3);

        $this->feedManager->move($medias[0], -1);
        $this->feedManager->move($medias[2], 1);

        self::assertSame(['Notification 1', 'Notification 2', 'Notification 3'], $this->titles());
    }

    public function testNormalizeRepairsSparsePositions(): void
    {
        $this->mediaFactory->createNotification(5, 'A');
        $this->mediaFactory->createNotification(12, 'B');
        $this->mediaFactory->createNotification(40, 'C');

        $this->feedManager->normalizePositions();

        self::assertSame([0, 1, 2], array_map(static fn (Media $m): int => $m->getPosition(), $this->mediaRepository->findAllOrdered()));
        self::assertSame(['A', 'B', 'C'], $this->titles());
    }

    private function newMedia(string $title): Media
    {
        $media = new Media();
        $media->setTitle($title)->setType(MediaType::TEXT)->setTextContent('x');

        return $media;
    }

    /**
     * @return list<string>
     */
    private function titles(): array
    {
        return array_map(static fn (Media $m): string => $m->getTitle(), $this->mediaRepository->findAllOrdered());
    }
}
