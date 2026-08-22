<?php

namespace App\Tests\Unit\Service;

use App\Entity\Media;
use App\Entity\MediaAccess;
use App\Entity\Pack;
use App\Entity\PackProgress;
use App\Entity\User;
use App\Repository\MediaAccessRepository;
use App\Repository\MediaRepository;
use App\Service\UnlockService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

class UnlockServiceTest extends TestCase
{
    private const NOW = '2026-01-01 12:00:00';

    public function testFirstMediaIsUnlockableWhenPackJustStarted(): void
    {
        $pack = $this->createPack(24);
        $medias = $this->createMedias($pack, 3);
        $progress = $this->createProgress($pack, lastOpenedAt: null);

        $states = $this->createService($medias, [])->getPackState($progress->getUser(), $progress);

        self::assertTrue($states[0]->unlockable, 'Le premier média doit être ouvrable immédiatement.');
        self::assertFalse($states[1]->unlockable, 'Le deuxième média doit rester verrouillé.');
        self::assertFalse($states[2]->unlockable);
    }

    public function testNextMediaIsLockedBeforeDelayHasElapsed(): void
    {
        $pack = $this->createPack(24);
        $medias = $this->createMedias($pack, 3);
        $progress = $this->createProgress($pack, lastOpenedAt: new \DateTimeImmutable('2026-01-01 06:00:00'));

        $states = $this->createService($medias, [$medias[0]])->getPackState($progress->getUser(), $progress);

        self::assertTrue($states[0]->opened);
        self::assertFalse($states[1]->unlockable, 'Seulement 6h écoulées sur 24h.');
        self::assertEquals(
            new \DateTimeImmutable('2026-01-02 06:00:00'),
            $states[1]->availableAt,
        );
    }

    public function testNextMediaIsUnlockableOnceDelayHasElapsed(): void
    {
        $pack = $this->createPack(24);
        $medias = $this->createMedias($pack, 3);
        $progress = $this->createProgress($pack, lastOpenedAt: new \DateTimeImmutable('2025-12-31 10:00:00'));

        $states = $this->createService($medias, [$medias[0]])->getPackState($progress->getUser(), $progress);

        self::assertTrue($states[1]->unlockable, '26h écoulées : le média suivant est ouvrable.');
        self::assertFalse($states[2]->unlockable, 'Le troisième reste verrouillé.');
    }

    public function testDelayIsExactlyElapsed(): void
    {
        $pack = $this->createPack(24);
        $medias = $this->createMedias($pack, 2);
        $progress = $this->createProgress($pack, lastOpenedAt: new \DateTimeImmutable('2025-12-31 12:00:00'));

        $states = $this->createService($medias, [$medias[0]])->getPackState($progress->getUser(), $progress);

        self::assertTrue($states[1]->unlockable, 'À la seconde exacte, le média doit être ouvrable.');
    }

    public function testOnlyOneMediaUnlocksEvenAfterLongAbsence(): void
    {
        $pack = $this->createPack(24);
        $medias = $this->createMedias($pack, 4);
        $progress = $this->createProgress($pack, lastOpenedAt: new \DateTimeImmutable('2025-12-01 12:00:00'));

        $states = $this->createService($medias, [$medias[0]])->getPackState($progress->getUser(), $progress);

        self::assertTrue($states[1]->unlockable);
        self::assertFalse($states[2]->unlockable, 'Une absence prolongée ne débloque pas plusieurs médias.');
        self::assertFalse($states[3]->unlockable);
    }

    public function testOpenedMediaStaysAccessible(): void
    {
        $pack = $this->createPack(24);
        $medias = $this->createMedias($pack, 3);
        $progress = $this->createProgress($pack, lastOpenedAt: new \DateTimeImmutable(self::NOW));

        $states = $this->createService($medias, [$medias[0], $medias[1]])->getPackState($progress->getUser(), $progress);

        self::assertTrue($states[0]->isAccessible());
        self::assertTrue($states[1]->isAccessible());
        self::assertFalse($states[2]->isAccessible(), 'Le délai vient de repartir.');
    }

    public function testCustomDelayIsHonoured(): void
    {
        $pack = $this->createPack(1);
        $medias = $this->createMedias($pack, 2);
        $progress = $this->createProgress($pack, lastOpenedAt: new \DateTimeImmutable('2026-01-01 11:30:00'));

        $states = $this->createService($medias, [$medias[0]])->getPackState($progress->getUser(), $progress);

        self::assertFalse($states[1]->unlockable, '30 min écoulées sur 1h.');

        $progress->setLastOpenedAt(new \DateTimeImmutable('2026-01-01 10:30:00'));
        $states = $this->createService($medias, [$medias[0]])->getPackState($progress->getUser(), $progress);

        self::assertTrue($states[1]->unlockable, '1h30 écoulée sur 1h.');
    }

    public function testCanOpenRejectsLockedMedia(): void
    {
        $pack = $this->createPack(24);
        $medias = $this->createMedias($pack, 3);
        $progress = $this->createProgress($pack, lastOpenedAt: new \DateTimeImmutable(self::NOW));
        $service = $this->createService($medias, [$medias[0]]);

        self::assertTrue($service->canOpen($progress->getUser(), $progress, $medias[0]));
        self::assertFalse($service->canOpen($progress->getUser(), $progress, $medias[1]));
        self::assertFalse($service->canOpen($progress->getUser(), $progress, $medias[2]));
    }

    public function testCanOpenRejectsMediaFromAnotherPack(): void
    {
        $pack = $this->createPack(24);
        $medias = $this->createMedias($pack, 2);
        $progress = $this->createProgress($pack, lastOpenedAt: null);

        $foreignMedia = new Media();
        $this->setId($foreignMedia, 999);

        self::assertFalse(
            $this->createService($medias, [])->canOpen($progress->getUser(), $progress, $foreignMedia),
            'Un média hors du pack courant ne doit jamais être ouvrable.',
        );
    }

    public function testEmptyPackYieldsNoState(): void
    {
        $pack = $this->createPack(24);
        $progress = $this->createProgress($pack, lastOpenedAt: null);

        self::assertSame([], $this->createService([], [])->getPackState($progress->getUser(), $progress));
    }

    public function testNextAvailabilityDateIsNullBeforeFirstOpening(): void
    {
        $pack = $this->createPack(24);
        $progress = $this->createProgress($pack, lastOpenedAt: null);

        self::assertNull($this->createService([], [])->getNextAvailabilityDate($progress));
    }

    /**
     * @param Media[] $medias
     * @param Media[] $opened
     */
    private function createService(array $medias, array $opened): UnlockService
    {
        $mediaRepository = self::createStub(MediaRepository::class);
        $mediaRepository->method('findByPackOrdered')->willReturn($medias);

        $accesses = array_map(
            function (Media $media): MediaAccess {
                $access = new MediaAccess();
                $access->setMedia($media);

                return $access;
            },
            $opened,
        );

        $accessRepository = self::createStub(MediaAccessRepository::class);
        $accessRepository->method('findForUserAndPack')->willReturn($accesses);

        return new UnlockService($mediaRepository, $accessRepository, new MockClock(self::NOW));
    }

    private function createPack(int $delayHours): Pack
    {
        $pack = new Pack();
        $pack->setName('Pack de test')->setUnlockDelayHours($delayHours);
        $this->setId($pack, 1);

        return $pack;
    }

    /**
     * @return Media[]
     */
    private function createMedias(Pack $pack, int $count): array
    {
        $medias = [];

        for ($i = 0; $i < $count; ++$i) {
            $media = new Media();
            $media->setTitle(sprintf('Média %d', $i))->setPosition($i)->setPack($pack);
            $this->setId($media, $i + 1);
            $medias[] = $media;
        }

        return $medias;
    }

    private function createProgress(Pack $pack, ?\DateTimeImmutable $lastOpenedAt): PackProgress
    {
        $user = new User();
        $user->setEmail('dorian@example.com');
        $this->setId($user, 1);

        $progress = new PackProgress();
        $progress->setUser($user)->setPack($pack)->setLastOpenedAt($lastOpenedAt);

        return $progress;
    }

    private function setId(object $entity, int $id): void
    {
        $reflection = new \ReflectionProperty($entity, 'id');
        $reflection->setValue($entity, $id);
    }
}
