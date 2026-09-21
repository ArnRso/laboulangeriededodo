<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Media;
use App\Entity\Tag;
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

    public function testANewNotificationWaitsTwoDaysByDefault(): void
    {
        self::assertSame(2880, new Media()->getDelayMinutes());
    }

    public function testATagIsNotAttachedTwice(): void
    {
        $media = new Media();
        $tag = new Tag();
        $tag->setName('Voyage');

        $media->addTag($tag)->addTag($tag);

        self::assertCount(1, $media->getTags());
        self::assertTrue($media->hasTag($tag));
    }

    public function testATagCanBeDetached(): void
    {
        $media = new Media();
        $tag = new Tag();
        $tag->setName('Voyage');
        $media->addTag($tag);

        $media->removeTag($tag);

        self::assertCount(0, $media->getTags());
        self::assertFalse($media->hasTag($tag));
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
