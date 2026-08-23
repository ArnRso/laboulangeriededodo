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
            [
                'app' => AppKind::WHATSAPP,
                'title' => 'Le message de 4 h 12',
                'description' => 'On ne l\'a jamais supprimé. On l\'a imprimé.',
                'type' => MediaType::TEXT,
                'content' => 'jsuis dehors depuis 20 min vous êtes où',
                'delay' => 30,
                'aura' => 100,
                'appData' => [
                    'contact' => 'Dodo du passé',
                    'contactEmoji' => '👶',
                    'statusLine' => 'vu il y a 11 ans',
                    'conversation' => "moi: c'est qui ???\ntoi, en 2015. tu avais dit « je reste une heure »\nmoi: et ?\nil était 4h12.",
                ],
            ],
            [
                'app' => AppKind::TIKTOK,
                'title' => 'POV : tu découvres le karaoké',
                'description' => 'Le son est resté dans la tête de tout le monde. Contre leur gré.',
                'type' => MediaType::TEXT,
                'content' => 'Ce soir-là, trois chansons. Zéro note juste. Une salle entière conquise.',
                'delay' => 1440,
                'aura' => 100,
                'appData' => [
                    'username' => 'dodo.du.passe',
                    'sound' => 'son original – dodo.du.passe · karaoké (sped up)',
                    'likes' => 48200,
                    'comments' => 1312,
                    'shares' => 2015,
                    'hashtags' => '#pov #canonevent #lostmedia #fyp',
                ],
            ],
            [
                'app' => AppKind::SPOTIFY,
                'title' => 'La chanson du bus',
                'description' => 'Tu connais encore toutes les paroles. Tu le nies. On a les vidéos.',
                'type' => MediaType::LINK,
                'content' => 'https://open.spotify.com/',
                'delay' => 720,
                'aura' => 100,
                'appData' => [
                    'artist' => 'Dodo du passé',
                    'album' => 'Lost Media (Deluxe)',
                    'playlist' => 'Tes années lycée',
                    'duration' => '3:47',
                    'plays' => '1 240 écoutes',
                    'progress' => 42,
                ],
            ],
            [
                'app' => AppKind::REVOLUT,
                'title' => 'La tournée que tu n\'as jamais payée',
                'description' => 'Le barman s\'en souvient. Nous aussi. Ton compte, moins.',
                'type' => MediaType::TEXT,
                'content' => 'Reçu retrouvé dans une poche de veste. Pas la tienne.',
                'delay' => 1440,
                'aura' => -200,
                'auraMessage' => 'Paiement refusé par ta dignité.',
                'appData' => [
                    'counterparty' => 'Le bar de 2015',
                    'reference' => 'CB 2015 TOURNÉE GÉNÉRALE',
                    'cardLast4' => '2015',
                    'category' => 'Décisions',
                    'statusLabel' => 'Contesté',
                ],
            ],
            [
                'app' => AppKind::METEO,
                'title' => 'Le jour du déménagement',
                'description' => 'Prévisions : pluie, cartons mal fermés, canapé coincé dans l\'escalier. Toutes confirmées.',
                'type' => MediaType::TEXT,
                'content' => 'On t\'avait dit de démonter les pieds du canapé.',
                'delay' => 1440,
                'aura' => 100,
                'appData' => [
                    'city' => 'Ton premier appart',
                    'temperature' => 9,
                    'condition' => 'Pluie avec risque de drama',
                    'high' => 12,
                    'low' => 6,
                    'hourly' => "08h 🌧️ 7°\n10h 🌧️ 8°\n12h 🛋️ 9°\n14h 😤 11°\n18h 🍕 12°",
                    'dramaIndex' => 91,
                ],
            ],
            [
                'app' => AppKind::CALENDAR,
                'title' => 'Ton anniversaire',
                'description' => 'Tu as dit « pas de fête ». Il y a eu une fête. Tu as fini par chanter.',
                'type' => MediaType::TEXT,
                'content' => 'Tout le monde avait prévu le coup. Toi seul a été surpris, comme chaque année.',
                'delay' => 1440,
                'aura' => 250,
                'appData' => [
                    'date' => 'Samedi 23 août 2015',
                    'timeRange' => '21:00 – 04:12',
                    'location' => 'Chez quelqu\'un dont tu as oublié le nom',
                    'attendees' => "Dodo du passé\nMarie\nTa mère (par téléphone)",
                    'alert' => 'Il y a 11 ans',
                    'calendarName' => 'Canon events',
                ],
            ],
        ];
    }
}
