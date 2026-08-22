<?php

namespace App\DataFixtures;

use App\Entity\Media;
use App\Entity\Pack;
use App\Entity\User;
use App\Enum\MediaType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // Mots de passe volontairement courts : les fixtures ne passent pas par
        // la validation du formulaire, et ces comptes ne servent qu'en local.
        $marie = new User();
        $marie->setEmail('marie@test.com')
            ->setRoles([User::ROLE_ADMIN])
            ->setPassword($this->passwordHasher->hashPassword($marie, 'Marie'));
        $manager->persist($marie);

        $dorian = new User();
        $dorian->setEmail('dorian@test.com')
            ->setRoles([User::ROLE_RECIPIENT])
            ->setPassword($this->passwordHasher->hashPassword($dorian, 'Dorian'));
        $manager->persist($dorian);

        foreach ($this->packDefinitions() as $position => $definition) {
            $pack = new Pack();
            $pack->setName($definition['name'])
                ->setDescription($definition['description'])
                ->setUnlockDelayMinutes($definition['delay'])
                ->setPosition($position)
                ->setPublished(true);
            $manager->persist($pack);

            foreach ($definition['medias'] as $mediaPosition => $mediaDefinition) {
                $media = new Media();
                $media->setPack($pack)
                    ->setPosition($mediaPosition)
                    ->setTitle($mediaDefinition['title'])
                    ->setDescription($mediaDefinition['description'])
                    ->setType($mediaDefinition['type']);

                if (MediaType::TEXT === $mediaDefinition['type']) {
                    $media->setTextContent($mediaDefinition['content']);
                } elseif (MediaType::LINK === $mediaDefinition['type']) {
                    $media->setUrl($mediaDefinition['content']);
                }

                $manager->persist($media);
            }
        }

        $manager->flush();
    }

    /**
     * @return array<int, array{name: string, description: string, delay: int, medias: array<int, array{title: string, description: string, type: MediaType, content: string}>}>
     */
    private function packDefinitions(): array
    {
        return [
            [
                'name' => 'Nos années lycée',
                'description' => 'Retour sur les débuts, les fous rires et les mauvaises coupes de cheveux.',
                'delay' => 1440,
                'medias' => [
                    [
                        'title' => 'Le premier jour',
                        'description' => 'Là où tout a commencé.',
                        'type' => MediaType::TEXT,
                        'content' => 'Tu te souviens de ce jour de septembre ? Moi oui.',
                    ],
                    [
                        'title' => 'La chanson de l\'époque',
                        'description' => 'Impossible de l\'oublier.',
                        'type' => MediaType::LINK,
                        'content' => 'https://open.spotify.com/',
                    ],
                    [
                        'title' => 'Le voyage scolaire',
                        'description' => 'Trois jours mémorables.',
                        'type' => MediaType::TEXT,
                        'content' => 'Le bus, la pluie, et cette partie de cartes interminable.',
                    ],
                ],
            ],
            [
                'name' => 'Les grandes aventures',
                'description' => 'Les voyages, les projets fous et les nuits blanches.',
                'delay' => 720,
                'medias' => [
                    [
                        'title' => 'Le road trip',
                        'description' => 'Sans GPS, évidemment.',
                        'type' => MediaType::TEXT,
                        'content' => 'On s\'est perdus trois fois. C\'était le meilleur moment.',
                    ],
                    [
                        'title' => 'La vidéo que tu croyais perdue',
                        'description' => 'Elle a refait surface.',
                        'type' => MediaType::LINK,
                        'content' => 'https://www.youtube.com/',
                    ],
                ],
            ],
        ];
    }
}
