<?php

namespace App\Tests\Integration\Service;

use App\Entity\Media;
use App\Enum\AppKind;
use App\Enum\MediaType;
use App\Service\Dressing\Source;
use App\Service\Dressing\SourceCatalog;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SourceCatalogTest extends KernelTestCase
{
    private SourceCatalog $catalog;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->catalog = self::getContainer()->get(SourceCatalog::class);
    }

    public function testADraftOffersItsTextsThenItsFragmentsThenFreeText(): void
    {
        $draft = new Media();
        $draft->setTitle('Le message de 4 h 12')
            ->setDescription('Tu avais dit une heure.')
            ->setType(MediaType::TEXT)
            ->setTextContent('jsuis dehors depuis 20 min')
            ->setFragments([['label' => 'Marie', 'text' => "j'étais là"], ['label' => '', 'text' => 'Une phrase assez longue pour être coupée dans le libellé de la source']]);

        $sources = $this->catalog->for($draft);

        self::assertSame(['title', 'description', 'memory', 'fragment:0', 'fragment:1', 'custom'], self::keys($sources));
        self::assertSame('Fragment · Marie', $sources[3]->label);
        self::assertSame('Fragment · Une phrase assez longue pour être coupée…', $sources[4]->label, 'Sans étiquette, un extrait du texte sert de repère.');
    }

    public function testTheMemoryIsOfferedOnlyWhenItIsAText(): void
    {
        $link = new Media();
        $link->setTitle('Lien')->setType(MediaType::LINK)->setUrl('https://example.com')->setTextContent('ignoré');

        self::assertSame(['title', 'url', 'custom'], self::keys($this->catalog->for($link)));
    }

    public function testADressedNotificationOffersItsCurrentValuesButNotItsBooleans(): void
    {
        $media = new Media();
        $media->setTitle('Habillée')->setType(MediaType::TEXT)->setTextContent('x')->setAppKind(AppKind::NETFLIX)
            ->setAppData(['showTitle' => 'Le film', 'synopsis' => '', 'match' => 98, 'topTen' => true, 'cast' => '  ']);

        $sources = $this->catalog->for($media);
        $keys = self::keys($sources);

        self::assertContains('app:showTitle', $keys);
        self::assertContains('app:match', $keys, 'Un entier a un texte à offrir.');
        self::assertNotContains('app:topTen', $keys, 'Un booléen n\'a pas de texte.');
        self::assertNotContains('app:synopsis', $keys, 'Une valeur vide n\'a rien à offrir.');
        self::assertNotContains('app:cast', $keys);
        $index = array_search('app:showTitle', $keys, true);
        self::assertIsInt($index);
        self::assertSame('Netflix · Titre de la fiche', $sources[$index]->label);
        self::assertSame('custom', end($keys));
    }

    /**
     * @param list<Source> $sources
     *
     * @return list<string>
     */
    private static function keys(array $sources): array
    {
        return array_map(static fn (Source $source): string => $source->key, $sources);
    }
}
