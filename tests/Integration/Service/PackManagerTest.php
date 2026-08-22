<?php

namespace App\Tests\Integration\Service;

use App\Entity\Media;
use App\Enum\MediaType;
use App\Repository\MediaRepository;
use App\Repository\PackRepository;
use App\Service\PackManager;
use App\Tests\Factory\PackFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class PackManagerTest extends KernelTestCase
{
    private PackManager $packManager;
    private PackFactory $packFactory;
    private MediaRepository $mediaRepository;
    private PackRepository $packRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->packManager = $container->get(PackManager::class);
        $this->mediaRepository = $container->get(MediaRepository::class);
        $this->packRepository = $container->get(PackRepository::class);
        $this->packFactory = new PackFactory($container->get(EntityManagerInterface::class));
    }

    public function testNewMediaIsAppendedAtTheEnd(): void
    {
        $pack = $this->packFactory->createPack();
        $this->packFactory->createMedias($pack, 3);

        $media = new Media();
        $media->setTitle('Nouveau')->setType(MediaType::TEXT)->setTextContent('x');
        $this->packManager->addMedia($pack, $media);

        self::assertSame(3, $media->getPosition(), 'Le média rejoint la fin de la séquence.');
    }

    public function testFirstMediaOfAPackStartsAtZero(): void
    {
        $pack = $this->packFactory->createPack();

        $media = new Media();
        $media->setTitle('Premier')->setType(MediaType::TEXT)->setTextContent('x');
        $this->packManager->addMedia($pack, $media);

        self::assertSame(0, $media->getPosition());
    }

    public function testDeletingMediaClosesTheGap(): void
    {
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 4);

        $this->packManager->deleteMedia($medias[1]);

        $remaining = $this->mediaRepository->findByPackOrdered($pack);

        self::assertCount(3, $remaining);
        self::assertSame([0, 1, 2], array_map(static fn (Media $m): int => $m->getPosition(), $remaining));
        self::assertSame(
            ['Média 1', 'Média 3', 'Média 4'],
            array_map(static fn (Media $m): string => $m->getTitle(), $remaining),
            'L\'ordre relatif des médias restants est préservé.',
        );
    }

    public function testMoveMediaDown(): void
    {
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);

        $this->packManager->moveMedia($medias[0], 1);

        self::assertSame(
            ['Média 2', 'Média 1', 'Média 3'],
            $this->titlesInOrder($pack),
        );
    }

    public function testMoveMediaUp(): void
    {
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);

        $this->packManager->moveMedia($medias[2], -1);

        self::assertSame(
            ['Média 1', 'Média 3', 'Média 2'],
            $this->titlesInOrder($pack),
        );
    }

    public function testMovingFirstMediaUpDoesNothing(): void
    {
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);

        $this->packManager->moveMedia($medias[0], -1);

        self::assertSame(['Média 1', 'Média 2', 'Média 3'], $this->titlesInOrder($pack));
    }

    public function testMovingLastMediaDownDoesNothing(): void
    {
        $pack = $this->packFactory->createPack();
        $medias = $this->packFactory->createMedias($pack, 3);

        $this->packManager->moveMedia($medias[2], 1);

        self::assertSame(['Média 1', 'Média 2', 'Média 3'], $this->titlesInOrder($pack));
    }

    public function testMoveDoesNotAffectOtherPacks(): void
    {
        $pack = $this->packFactory->createPack('Premier pack');
        $other = $this->packFactory->createPack('Second pack', position: 1);
        $medias = $this->packFactory->createMedias($pack, 2);
        $this->packFactory->createMedias($other, 2);

        $this->packManager->moveMedia($medias[0], 1);

        self::assertSame(['Média 1', 'Média 2'], $this->titlesInOrder($other), 'L\'autre pack est intact.');
    }

    public function testNormalizeRepairsSparsePositions(): void
    {
        $pack = $this->packFactory->createPack();
        $this->packFactory->createMedia($pack, 5, 'A');
        $this->packFactory->createMedia($pack, 12, 'B');
        $this->packFactory->createMedia($pack, 40, 'C');

        $this->packManager->normalizeMediaPositions($pack);

        $medias = $this->mediaRepository->findByPackOrdered($pack);

        self::assertSame([0, 1, 2], array_map(static fn (Media $m): int => $m->getPosition(), $medias));
        self::assertSame(['A', 'B', 'C'], array_map(static fn (Media $m): string => $m->getTitle(), $medias));
    }

    public function testNewPackIsAppendedAtTheEnd(): void
    {
        $this->packFactory->createPack('Existant', position: 0);

        $pack = new \App\Entity\Pack();
        $pack->setName('Nouveau');
        $this->packManager->createPack($pack);

        self::assertSame(1, $pack->getPosition());
    }

    public function testDeletingPackRemovesItsMedias(): void
    {
        $pack = $this->packFactory->createPack();
        $this->packFactory->createMedias($pack, 3);
        $packId = $pack->getId();

        $this->packManager->deletePack($pack);

        self::assertNull($this->packRepository->find((int) $packId));
        self::assertCount(0, $this->mediaRepository->findAll(), 'Les médias orphelins sont supprimés.');
    }

    /**
     * @return list<string>
     */
    private function titlesInOrder(\App\Entity\Pack $pack): array
    {
        return array_map(
            static fn (Media $m): string => $m->getTitle(),
            $this->mediaRepository->findByPackOrdered($pack),
        );
    }
}
