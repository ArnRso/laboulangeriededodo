<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Media;
use App\Enum\AppKind;
use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase
{
    public function testFragmentsAreNormalised(): void
    {
        $media = new Media();
        $media->setFragments([
            ['label' => '  Légende ', 'text' => ' Tu avais dit une heure. '],
            ['label' => 'Vide', 'text' => "   \n "],
            ['text' => 'Sans étiquette'],
        ]);

        self::assertSame([
            ['label' => 'Légende', 'text' => 'Tu avais dit une heure.'],
            ['label' => '', 'text' => 'Sans étiquette'],
        ], $media->getFragments());
    }

    public function testAddFragmentIgnoresATextAlreadyKept(): void
    {
        $media = new Media();
        $media->addFragment('Deliveroo · Nom du plat', 'Le premier jour');
        $media->addFragment('Autre étiquette', 'Le premier jour');
        $media->addFragment('Deliveroo · Instructions', 'Sonne deux fois');

        self::assertCount(2, $media->getFragments());
        self::assertSame('Deliveroo · Nom du plat', $media->getFragments()[0]['label']);
    }

    public function testTagsAreNormalised(): void
    {
        $media = new Media();
        $media->setTags(['  Souvenirs ', '', "Voyage\n  Italie", '   ']);

        self::assertSame(['Souvenirs', 'Voyage Italie'], $media->getTags());
    }

    public function testTheSameTagIsNotKeptTwiceWhateverTheCase(): void
    {
        $media = new Media();
        $media->setTags(['Voyage', 'voyage', 'VOYAGE', 'Cadeau']);

        self::assertSame(['Voyage', 'Cadeau'], $media->getTags());
    }

    public function testATagIsFoundWhateverTheCase(): void
    {
        $media = new Media();
        $media->setTags(['Voyage']);

        self::assertTrue($media->hasTag('voyage'));
        self::assertTrue($media->hasTag('VOYAGE'));
        self::assertFalse($media->hasTag('voyages'));
    }

    public function testNonStringTagsAreDropped(): void
    {
        $media = new Media();
        $media->setTags(['Voyage', 42, null, ['imbriqué'], 'Cadeau']);

        self::assertSame(['Voyage', 'Cadeau'], $media->getTags());
    }

    public function testADraftHasNoAppToRequire(): void
    {
        $draft = new Media();
        $draft->setTitle('Brouillon');

        self::assertTrue($draft->isDraft());

        $this->expectException(\LogicException::class);

        $draft->requireAppKind();
    }

    public function testADressedNotificationGivesItsApp(): void
    {
        $media = new Media();
        $media->setTitle('Habillée')->setAppKind(AppKind::INSTAGRAM);

        self::assertFalse($media->isDraft());
        self::assertSame(AppKind::INSTAGRAM, $media->requireAppKind());
    }
}
