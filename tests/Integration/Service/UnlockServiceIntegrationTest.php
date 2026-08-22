<?php

namespace App\Tests\Integration\Service;

use App\Entity\Media;
use App\Entity\MediaAccess;
use App\Entity\Pack;
use App\Entity\PackProgress;
use App\Entity\User;
use App\Enum\MediaType;
use App\Service\UnlockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Valide le UnlockService contre une vraie base de données : les tests unitaires
 * simulent les repositories, ceux-ci vérifient que les requêtes Doctrine sont justes.
 */
class UnlockServiceIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UnlockService $unlockService;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->unlockService = self::getContainer()->get(UnlockService::class);
    }

    public function testMediasAreOrderedByPosition(): void
    {
        $pack = $this->createPack(1440);
        // Insertion volontairement désordonnée pour vérifier le tri en base.
        $this->createMedia($pack, 2, 'Troisième');
        $this->createMedia($pack, 0, 'Premier');
        $this->createMedia($pack, 1, 'Deuxième');
        $user = $this->createUser();
        $progress = $this->createProgress($user, $pack, null);
        $this->entityManager->flush();

        $states = $this->unlockService->getPackState($user, $progress);

        self::assertCount(3, $states);
        self::assertSame('Premier', $states[0]->media->getTitle());
        self::assertSame('Deuxième', $states[1]->media->getTitle());
        self::assertSame('Troisième', $states[2]->media->getTitle());
    }

    public function testOpenedMediaIsDetectedFromDatabase(): void
    {
        $pack = $this->createPack(1440);
        $first = $this->createMedia($pack, 0, 'Premier');
        $this->createMedia($pack, 1, 'Deuxième');
        $user = $this->createUser();
        $progress = $this->createProgress($user, $pack, new \DateTimeImmutable('-1 hour'));

        $access = new MediaAccess();
        $access->setUser($user)->setMedia($first);
        $this->entityManager->persist($access);
        $this->entityManager->flush();

        $states = $this->unlockService->getPackState($user, $progress);

        self::assertTrue($states[0]->opened, 'L\'ouverture enregistrée doit être relue depuis la base.');
        self::assertFalse($states[1]->unlockable, 'Le délai de 24h n\'est pas écoulé.');
    }

    public function testAccessOfAnotherUserDoesNotLeak(): void
    {
        $pack = $this->createPack(1440);
        $first = $this->createMedia($pack, 0, 'Premier');
        $dorian = $this->createUser('dorian@example.com');
        $someoneElse = $this->createUser('autre@example.com');

        $access = new MediaAccess();
        $access->setUser($someoneElse)->setMedia($first);
        $this->entityManager->persist($access);

        $progress = $this->createProgress($dorian, $pack, null);
        $this->entityManager->flush();

        $states = $this->unlockService->getPackState($dorian, $progress);

        self::assertFalse(
            $states[0]->opened,
            'L\'ouverture par un autre utilisateur ne doit pas compter pour Dorian.',
        );
    }

    public function testMediaFromAnotherPackIsNotCounted(): void
    {
        $pack = $this->createPack(1440);
        $otherPack = $this->createPack(1440);
        $this->createMedia($pack, 0, 'Dans le pack');
        $foreign = $this->createMedia($otherPack, 0, 'Hors du pack');
        $user = $this->createUser();

        $access = new MediaAccess();
        $access->setUser($user)->setMedia($foreign);
        $this->entityManager->persist($access);

        $progress = $this->createProgress($user, $pack, null);
        $this->entityManager->flush();

        $states = $this->unlockService->getPackState($user, $progress);

        self::assertCount(1, $states, 'Seuls les médias du pack courant sont retournés.');
        self::assertFalse($states[0]->opened);
    }

    private function createPack(int $delayMinutes): Pack
    {
        $pack = new Pack();
        $pack->setName('Pack de test')->setUnlockDelayMinutes($delayMinutes)->setPublished(true);
        $this->entityManager->persist($pack);

        return $pack;
    }

    private function createMedia(Pack $pack, int $position, string $title): Media
    {
        $media = new Media();
        $media->setPack($pack)
            ->setPosition($position)
            ->setTitle($title)
            ->setType(MediaType::TEXT)
            ->setTextContent('Contenu');
        $this->entityManager->persist($media);

        return $media;
    }

    private function createUser(string $email = 'dorian@example.com'): User
    {
        $user = new User();
        $user->setEmail($email)->setRoles([User::ROLE_RECIPIENT])->setPassword('hash');
        $this->entityManager->persist($user);

        return $user;
    }

    private function createProgress(User $user, Pack $pack, ?\DateTimeImmutable $lastOpenedAt): PackProgress
    {
        $progress = new PackProgress();
        $progress->setUser($user)->setPack($pack)->setLastOpenedAt($lastOpenedAt);
        $this->entityManager->persist($progress);

        return $progress;
    }
}
