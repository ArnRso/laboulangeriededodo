<?php

namespace App\Tests\Integration\Service;

use App\Enum\AppKind;
use App\Form\AppDetails\AppDetailsRegistry;
use App\Service\Dressing\DressMapping;
use App\Service\Dressing\NotificationDresser;
use App\Service\FeedService;
use App\Tests\Factory\MediaFactory;
use App\Tests\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class NotificationDresserTest extends KernelTestCase
{
    private NotificationDresser $dresser;
    private AppDetailsRegistry $registry;
    private MediaFactory $mediaFactory;
    private UserFactory $userFactory;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->dresser = $container->get(NotificationDresser::class);
        $this->registry = $container->get(AppDetailsRegistry::class);
        $entityManager = $container->get(EntityManagerInterface::class);
        $this->mediaFactory = new MediaFactory($entityManager);
        $this->userFactory = new UserFactory($entityManager, $container->get(UserPasswordHasherInterface::class));
    }

    public function testDressingADraftSetsTheAppAndComposesItsDataWithoutPublishing(): void
    {
        $draft = $this->mediaFactory->createDraft(0, 'Le message de 4 h 12', 'Tu avais dit une heure.', [['label' => 'Marie', 'text' => "marie: j'étais là"]]);

        $this->dresser->dress($draft, AppKind::INSTAGRAM, DressMapping::fromFormData(['fields' => [
            'caption' => ['source' => 'title'],
            'comments' => ['sources' => ['fragment:0']],
        ]]));

        self::assertSame(AppKind::INSTAGRAM, $draft->getAppKind());
        self::assertFalse($draft->isPublished());
        self::assertSame('Le message de 4 h 12', $draft->getAppData()['caption']);
        self::assertSame("marie: j'étais là", $draft->getAppData()['comments']);
        self::assertSame('dodo.du.passe', $draft->getAppData()['username'], 'Un champ non mappé garde le défaut de l\'app.');
        self::assertSame(array_keys($this->registry->defaultsFor(AppKind::INSTAGRAM)), array_keys($draft->getAppData()));
    }

    public function testChangingTheAppArchivesOnlyTheTypedValuesTheMappingLeavesBehind(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'Le premier jour', AppKind::DELIVEROO);
        $media->setAppData([...$this->registry->defaultsFor(AppKind::DELIVEROO), 'dishName' => 'Le premier jour, en sauce', 'instructions' => 'Sonne deux fois, il dort']);
        $media->setPublished(true);

        $this->dresser->dress($media, AppKind::INSTAGRAM, DressMapping::fromFormData(['fields' => [
            'caption' => ['source' => 'app:dishName'],
        ]]));

        self::assertSame(AppKind::INSTAGRAM, $media->getAppKind());
        self::assertTrue($media->isPublished(), 'Habiller ne touche pas à la publication.');
        self::assertSame('Le premier jour, en sauce', $media->getAppData()['caption']);
        self::assertSame(
            [['label' => 'Deliveroo · Instructions de livraison', 'text' => 'Sonne deux fois, il dort']],
            $media->getFragments(),
            'La valeur reprise n\'est pas archivée ; les défauts (« Chez Dodo ») non plus ; la valeur abandonnée l\'est.',
        );
    }

    public function testDressingTwiceDoesNotDuplicateFragmentsAndKeepsTheExistingOnes(): void
    {
        $media = $this->mediaFactory->createNotification(0, 'Titre', AppKind::DELIVEROO);
        $media->setAppData([...$this->registry->defaultsFor(AppKind::DELIVEROO), 'instructions' => 'Sonne deux fois'])
            ->addFragment('Marie', "j'étais là");

        $this->dresser->dress($media, AppKind::INSTAGRAM, DressMapping::empty());
        $this->dresser->dress($media, AppKind::DELIVEROO, DressMapping::empty());
        $this->dresser->dress($media, AppKind::INSTAGRAM, DressMapping::empty());

        self::assertSame([
            ['label' => 'Marie', 'text' => "j'étais là"],
            ['label' => 'Deliveroo · Instructions de livraison', 'text' => 'Sonne deux fois'],
        ], $media->getFragments());
    }

    public function testANotificationAlreadyOpenedCannotBeDressedAgain(): void
    {
        $dorian = $this->userFactory->createRecipient();
        $media = $this->mediaFactory->createNotification(0, 'Vue', AppKind::UBER_EATS, delayMinutes: 0);
        $untouched = $this->mediaFactory->createNotification(1, 'Pas encore vue', AppKind::UBER_EATS);

        self::assertTrue($this->dresser->canDress($media));

        self::getContainer()->get(FeedService::class)->open($dorian, $media);

        self::assertFalse($this->dresser->canDress($media));
        self::assertTrue($this->dresser->canDress($untouched));
    }
}
