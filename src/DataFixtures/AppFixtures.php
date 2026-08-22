<?php

namespace App\DataFixtures;

use App\Entity\Media;
use App\Entity\User;
use App\Enum\AppKind;
use App\Enum\Avatar;
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
            ->setDisplayName('Dodo')
            ->setAvatar(Avatar::DODO)
            ->setPassword($this->passwordHasher->hashPassword($dorian, 'Dorian'));
        $manager->persist($dorian);

        foreach ($this->notifications() as $position => $definition) {
            $media = new Media();
            $media->setPosition($position)
                ->setAppKind($definition['app'])
                ->setTitle($definition['title'])
                ->setDescription($definition['description'])
                ->setType($definition['type'])
                ->setDelayMinutes($definition['delay'])
                ->setAuraPoints($definition['aura'])
                ->setAuraMessage($definition['auraMessage'] ?? null)
                ->setAppData($definition['appData']);

            if (MediaType::TEXT === $definition['type']) {
                $media->setTextContent($definition['content']);
            } elseif (MediaType::LINK === $definition['type']) {
                $media->setUrl($definition['content']);
            }

            $manager->persist($media);
        }

        $manager->flush();
    }

    /**
     * @return list<array{app: AppKind, title: string, description: string, type: MediaType, content: string, delay: int, aura: int, auraMessage?: string, appData: array<string, mixed>}>
     */
    private function notifications(): array
    {
        return [
            [
                'app' => AppKind::UBER_EATS,
                'title' => 'Le premier jour',
                'description' => 'Là où tout a commencé. Septembre, un bus, et une coupe de cheveux que personne n\'a validée.',
                'type' => MediaType::TEXT,
                'content' => 'Tu te souviens de ce jour de septembre ? Moi oui. Toi tu avais dit « je reste une heure ». Il était 4 h 12.',
                'delay' => 0,
                'aura' => 100,
                'appData' => [
                    'courier' => 'Dodo du passé',
                    'trip' => 'Ton adolescence → Aujourd\'hui · 11 ans de trajet',
                    'stars' => 4,
                ],
            ],
            [
                'app' => AppKind::INSTAGRAM,
                'title' => 'La chanson de l\'époque',
                'description' => 'Impossible de l\'oublier. Toi non plus, d\'ailleurs, on t\'a entendu.',
                'type' => MediaType::LINK,
                'content' => 'https://open.spotify.com/',
                'delay' => 1440,
                'aura' => 100,
                'appData' => [
                    'username' => 'dodo.du.passe',
                    'location' => 'Nos années lycée · Septembre 2015',
                    'likedBy' => 'ta.mere',
                    'likesCount' => 1240,
                    'hashtags' => '#LoreUnlocked #CanonEvent #MainCharacterEnergy',
                    'comments' => "marie: tu avais dit « je reste une heure » 💀\ndodo.du.passe: il était 4h12 et je regrette rien",
                    'timeAgo' => 'Il y a 11 ans',
                    'badge' => 'Icon · Main character energy',
                ],
            ],
            [
                'app' => AppKind::TINDER,
                'title' => 'La coupe de 2015',
                'description' => 'Le voyage scolaire. Trois jours, un bus, la pluie, et cette décision capillaire prise à 7 h du matin dans un miroir de station-service.',
                'type' => MediaType::TEXT,
                'content' => 'Preuve photo à venir. Tu ne l\'as jamais supprimée, on l\'a retrouvée.',
                'delay' => 1440,
                'aura' => -500,
                'auraMessage' => 'Désolé. Cette décision était objectivement catastrophique.',
                'appData' => [
                    'matchName' => 'La coupe de 2015',
                    'matchAge' => 19,
                    'matchEmoji' => '💇',
                    'locationLine' => '📍 À 11 ans de toi · Encore en ligne, malheureusement',
                    'dramaLevel' => 87,
                    'chips' => "🚩 Red flag\n✂️ Dégradé non consenti\n📸 Preuve photo\n🎭 Canon event",
                ],
            ],
            [
                'app' => AppKind::DOCTOLIB,
                'title' => 'Le voyage scolaire',
                'description' => 'Le bus, la pluie, et cette partie de cartes interminable. Le patient a perdu trois fois de suite et a contesté les règles à chaque manche. Pronostic : aucune amélioration en onze ans.',
                'type' => MediaType::TEXT,
                'content' => 'Trois jours mémorables, dont deux passés à chercher ton sac.',
                'delay' => 60,
                'aura' => 100,
                'appData' => [
                    'practitioner' => 'Dr Passé',
                    'specialty' => 'Spécialiste des décisions catastrophiques',
                    'sector' => 'Conventionné secteur 2015',
                    'address' => "Ton adolescence\n2e étage, porte du fond · Interphone « Dodo »",
                    'refundLabel' => 'Pris en charge par la mutuelle du passé',
                ],
            ],
            [
                'app' => AppKind::UBER_EATS,
                'title' => 'Le road trip',
                'description' => 'Sans GPS, évidemment. On s\'est perdus trois fois. C\'était le meilleur moment.',
                'type' => MediaType::TEXT,
                'content' => 'Tu conduisais. Personne ne sait encore comment on est arrivés.',
                'delay' => 1440,
                'aura' => 100,
                'appData' => [
                    'courier' => 'Dodo du passé',
                    'trip' => 'Quelque part → Ailleurs · 3 détours',
                    'stars' => 5,
                ],
            ],
        ];
    }
}
