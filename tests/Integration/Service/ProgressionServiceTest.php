<?php

namespace App\Tests\Integration\Service;

use App\Entity\Pack;
use App\Entity\PackProgress;
use App\Service\ProgressionService;
use App\Tests\Factory\PackFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProgressionServiceTest extends KernelTestCase
{
    private ProgressionService $progressionService;
    private PackFactory $packFactory;
    private UserFactory $userFactory;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->progressionService = $container->get(ProgressionService::class);
        $this->packFactory = new PackFactory($this->entityManager);
        $this->userFactory = new UserFactory(
            $this->entityManager,
            $container->get(UserPasswordHasherInterface::class),
        );
    }

    public function testUnpublishedPacksAreNotSelectable(): void
    {
        $user = $this->userFactory->createRecipient();
        $published = $this->packFactory->createPack('Visible');
        $this->packFactory->createMedias($published, 2);
        $hidden = $this->packFactory->createPack('Masqué', published: false, position: 1);
        $this->packFactory->createMedias($hidden, 2);

        $selectable = $this->progressionService->getSelectablePacks($user);

        self::assertSame(['Visible'], array_map(static fn (Pack $p): string => $p->getName(), $selectable));
    }

    public function testEmptyPacksAreNotSelectable(): void
    {
        $user = $this->userFactory->createRecipient();
        $withMedia = $this->packFactory->createPack('Avec médias');
        $this->packFactory->createMedias($withMedia, 1);
        $this->packFactory->createPack('Vide', position: 1);

        $selectable = $this->progressionService->getSelectablePacks($user);

        self::assertSame(['Avec médias'], array_map(static fn (Pack $p): string => $p->getName(), $selectable));
    }

    public function testStartedPackIsNoLongerSelectable(): void
    {
        $user = $this->userFactory->createRecipient();
        $pack = $this->packFactory->createPack();
        $this->packFactory->createMedias($pack, 2);

        $this->progressionService->startPack($user, $pack);

        self::assertSame([], $this->progressionService->getSelectablePacks($user));
    }

    public function testCannotStartASecondPackWhileOneIsActive(): void
    {
        $user = $this->userFactory->createRecipient();
        $first = $this->packFactory->createPack('Premier');
        $this->packFactory->createMedias($first, 2);
        $second = $this->packFactory->createPack('Second', position: 1);
        $this->packFactory->createMedias($second, 2);

        $this->progressionService->startPack($user, $first);

        $this->expectException(\LogicException::class);
        $this->progressionService->startPack($user, $second);
    }

    public function testCannotStartAnUnpublishedPack(): void
    {
        $user = $this->userFactory->createRecipient();
        $hidden = $this->packFactory->createPack('Masqué', published: false);
        $this->packFactory->createMedias($hidden, 2);

        $this->expectException(\LogicException::class);
        $this->progressionService->startPack($user, $hidden);
    }

    public function testFirstMediaCanBeOpenedImmediately(): void
    {
        $user = $this->userFactory->createRecipient();
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);
        $progress = $this->progressionService->startPack($user, $pack);

        $access = $this->progressionService->openMedia($user, $progress, $medias[0]);

        self::assertSame($medias[0], $access->getMedia());
        self::assertNotNull($progress->getLastOpenedAt(), 'Le délai repart à l\'ouverture.');
    }

    public function testSecondMediaIsRefusedBeforeTheDelay(): void
    {
        $user = $this->userFactory->createRecipient();
        $pack = $this->packFactory->createPack(unlockDelayMinutes: 1440);
        $medias = $this->packFactory->createMedias($pack, 3);
        $progress = $this->progressionService->startPack($user, $pack);

        $this->progressionService->openMedia($user, $progress, $medias[0]);

        $this->expectException(\LogicException::class);
        $this->progressionService->openMedia($user, $progress, $medias[1]);
    }

    public function testSkippingAheadIsRefused(): void
    {
        $user = $this->userFactory->createRecipient();
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);
        $progress = $this->progressionService->startPack($user, $pack);

        $this->expectException(\LogicException::class);
        $this->progressionService->openMedia($user, $progress, $medias[2]);
    }

    public function testReopeningAnOpenedMediaDoesNotRestartTheDelay(): void
    {
        $user = $this->userFactory->createRecipient();
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);
        $progress = $this->progressionService->startPack($user, $pack);

        $first = $this->progressionService->openMedia($user, $progress, $medias[0]);
        $openedAt = $progress->getLastOpenedAt();

        $second = $this->progressionService->openMedia($user, $progress, $medias[0]);

        self::assertSame($first->getId(), $second->getId(), 'Aucun doublon d\'accès.');
        self::assertEquals($openedAt, $progress->getLastOpenedAt());
    }

    public function testOpeningTheLastMediaCompletesThePack(): void
    {
        $user = $this->userFactory->createRecipient();
        $pack = $this->packFactory->createPack(unlockDelayMinutes: 1440);
        $medias = $this->packFactory->createMedias($pack, 2);
        $progress = $this->progressionService->startPack($user, $pack);

        $this->progressionService->openMedia($user, $progress, $medias[0]);
        self::assertFalse($progress->isCompleted(), 'Le pack n\'est pas fini au premier média.');

        // Le délai est neutralisé pour atteindre le dernier média.
        $progress->setLastOpenedAt(new \DateTimeImmutable('-48 hours'));
        $this->entityManager->flush();

        $this->progressionService->openMedia($user, $progress, $medias[1]);

        self::assertTrue($progress->isCompleted());
        self::assertNull(
            $this->progressionService->getActiveProgress($user),
            'Aucun pack actif une fois celui-ci terminé.',
        );
    }

    public function testANewPackCanBeChosenAfterCompletion(): void
    {
        $user = $this->userFactory->createRecipient();
        $first = $this->packFactory->createPack('Premier');
        $medias = $this->packFactory->createMedias($first, 1);
        $second = $this->packFactory->createPack('Second', position: 1);
        $this->packFactory->createMedias($second, 2);

        $progress = $this->progressionService->startPack($user, $first);
        $this->progressionService->openMedia($user, $progress, $medias[0]);

        self::assertTrue($progress->isCompleted());
        self::assertSame(
            ['Second'],
            array_map(static fn (Pack $p): string => $p->getName(), $this->progressionService->getSelectablePacks($user)),
        );

        $newProgress = $this->progressionService->startPack($user, $second);
        self::assertSame($second, $newProgress->getPack());
    }

    public function testProgressOfOneUserDoesNotAffectAnother(): void
    {
        $dorian = $this->userFactory->createRecipient('dorian@example.com');
        $other = $this->userFactory->createRecipient('autre@example.com');
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 2);

        $progress = $this->progressionService->startPack($dorian, $pack);
        $this->progressionService->openMedia($dorian, $progress, $medias[0]);

        self::assertNull($this->progressionService->getActiveProgress($other));
        self::assertCount(1, $this->progressionService->getSelectablePacks($other));
    }

    public function testNoCompletedProgressBeforeAnyPackIsFinished(): void
    {
        $user = $this->userFactory->createRecipient();

        self::assertSame([], $this->progressionService->getCompletedProgresses($user));
    }

    public function testCompletedProgressesAreListed(): void
    {
        $user = $this->userFactory->createRecipient();
        $pack = $this->packFactory->createPack('Pack terminé');
        $medias = $this->packFactory->createMedias($pack, 1);

        $progress = $this->progressionService->startPack($user, $pack);
        $this->progressionService->openMedia($user, $progress, $medias[0]);

        $completedNames = array_map(
            static fn (PackProgress $p): string => $p->getPack()->getName(),
            $this->progressionService->getCompletedProgresses($user),
        );

        self::assertSame(['Pack terminé'], $completedNames);
    }

    public function testSinglePackIsCompletedByOpeningItsOnlyMedia(): void
    {
        $user = $this->userFactory->createRecipient();
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 1);

        $progress = $this->progressionService->startPack($user, $pack);
        $this->progressionService->openMedia($user, $progress, $medias[0]);

        self::assertTrue($progress->isCompleted());
    }
}
