<?php

namespace App\Tests\Unit\Service;

use App\Entity\Media;
use App\Entity\MediaAccess;
use App\Entity\User;
use App\Repository\MediaAccessRepository;
use App\Repository\MediaRepository;
use App\Service\FeedUnlockService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

class FeedUnlockServiceTest extends TestCase
{
    private const string NOW = '2026-01-01 12:00:00';

    public function testFirstNotificationIsAvailableImmediately(): void
    {
        $medias = $this->createFeed(3);

        $states = $this->createService($medias, [])->getFeedState($this->user());

        self::assertTrue($states[0]->unlockable, 'La première arrive sans attendre.');
        self::assertFalse($states[1]->unlockable);
        self::assertFalse($states[2]->unlockable);
    }

    public function testNextIsLockedBeforeItsDelayHasElapsed(): void
    {
        $medias = $this->createFeed(3);

        $states = $this->createService($medias, [
            [$medias[0], '2026-01-01 06:00:00'],
        ])->getFeedState($this->user());

        self::assertTrue($states[0]->opened);
        self::assertFalse($states[1]->unlockable, 'Seulement 6 h écoulées sur 24 h.');
        self::assertEquals(new \DateTimeImmutable('2026-01-02 06:00:00'), $states[1]->availableAt);
        self::assertTrue($states[1]->isNext());
    }

    public function testNextBecomesAvailableOnceItsDelayHasElapsed(): void
    {
        $medias = $this->createFeed(3);

        $states = $this->createService($medias, [
            [$medias[0], '2025-12-31 10:00:00'],
        ])->getFeedState($this->user());

        self::assertTrue($states[1]->unlockable, '26 h écoulées : elle est arrivée.');
        self::assertFalse($states[2]->unlockable, 'La troisième attend l\'ouverture de la deuxième.');
        self::assertNull($states[2]->availableAt, 'Son chrono n\'a pas encore commencé.');
    }

    public function testDelayIsExactlyElapsed(): void
    {
        $medias = $this->createFeed(2);

        $states = $this->createService($medias, [
            [$medias[0], '2025-12-31 12:00:00'],
        ])->getFeedState($this->user());

        self::assertTrue($states[1]->unlockable, 'À la seconde exacte, elle est disponible.');
    }

    public function testEachNotificationCarriesItsOwnDelay(): void
    {
        $medias = $this->createFeed(3);
        // La troisième enchaîne dix minutes après la deuxième.
        $medias[2]->setDelayMinutes(10);

        $states = $this->createService($medias, [
            [$medias[0], '2025-12-30 12:00:00'],
            [$medias[1], '2026-01-01 11:45:00'],
        ])->getFeedState($this->user());

        self::assertTrue($states[2]->unlockable, '15 min écoulées pour un délai de 10.');
    }

    public function testOnlyOneNotificationArrivesEvenAfterALongAbsence(): void
    {
        $medias = $this->createFeed(4);

        $states = $this->createService($medias, [
            [$medias[0], '2025-12-01 12:00:00'],
        ])->getFeedState($this->user());

        self::assertTrue($states[1]->unlockable);
        self::assertFalse($states[2]->unlockable, 'Une absence prolongée ne libère pas plusieurs notifications.');
        self::assertFalse($states[3]->unlockable);
    }

    public function testOpenedNotificationStaysAccessible(): void
    {
        $medias = $this->createFeed(3);

        $states = $this->createService($medias, [
            [$medias[0], '2025-12-31 12:00:00'],
            [$medias[1], self::NOW],
        ])->getFeedState($this->user());

        self::assertTrue($states[0]->isAccessible());
        self::assertTrue($states[1]->isAccessible());
        self::assertFalse($states[2]->isAccessible(), 'Le délai vient de repartir.');
    }

    public function testUnpublishedNotificationsAreInvisible(): void
    {
        $medias = $this->createFeed(3);
        $medias[1]->setPublished(false);
        $published = [$medias[0], $medias[2]];

        $states = $this->createService($published, [
            [$medias[0], '2025-12-30 12:00:00'],
        ])->getFeedState($this->user());

        self::assertCount(2, $states);
        self::assertSame($medias[2], $states[1]->media, 'Le brouillon est sauté, la suivante prend sa place.');
        self::assertTrue($states[1]->unlockable);
    }

    public function testCanOpenRejectsLockedNotification(): void
    {
        $medias = $this->createFeed(3);
        $service = $this->createService($medias, [[$medias[0], self::NOW]]);

        self::assertTrue($service->canOpen($this->user(), $medias[0]));
        self::assertFalse($service->canOpen($this->user(), $medias[1]));
        self::assertFalse($service->canOpen($this->user(), $medias[2]));
    }

    public function testCanOpenRejectsUnknownNotification(): void
    {
        $medias = $this->createFeed(2);
        $foreign = new Media();
        $foreign->setTitle('Hors fil');

        self::assertFalse($this->createService($medias, [])->canOpen($this->user(), $foreign));
    }

    public function testEmptyFeedYieldsNoState(): void
    {
        self::assertSame([], $this->createService([], [])->getFeedState($this->user()));
    }

    /**
     * @param list<Media> $medias
     * @param list<array{Media, string}> $opened média et date d'ouverture
     */
    private function createService(array $medias, array $opened): FeedUnlockService
    {
        $mediaRepository = self::createStub(MediaRepository::class);
        $mediaRepository->method('findPublishedOrdered')->willReturn($medias);

        $accesses = array_map(
            function (array $entry): MediaAccess {
                $access = new MediaAccess();
                $access->setMedia($entry[0])->setOpenedAt(new \DateTimeImmutable($entry[1]));

                return $access;
            },
            $opened,
        );

        $accessRepository = self::createStub(MediaAccessRepository::class);
        $accessRepository->method('findForUser')->willReturn($accesses);

        return new FeedUnlockService($mediaRepository, $accessRepository, new MockClock(self::NOW));
    }

    /**
     * @return list<Media>
     */
    private function createFeed(int $count): array
    {
        $medias = [];

        for ($i = 0; $i < $count; ++$i) {
            $media = new Media();
            $media->setTitle(sprintf('Notification %d', $i + 1))->setPosition($i)->setDelayMinutes(1440);
            $medias[] = $media;
        }

        return $medias;
    }

    private function user(): User
    {
        $user = new User();
        $user->setEmail('dorian@example.com');

        return $user;
    }
}
