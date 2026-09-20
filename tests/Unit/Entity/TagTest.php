<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Tag;
use PHPUnit\Framework\TestCase;

class TagTest extends TestCase
{
    public function testTheNameIsTrimmed(): void
    {
        $tag = new Tag();
        $tag->setName('  Souvenirs  ');

        self::assertSame('Souvenirs', $tag->getName());
    }

    public function testInnerSpacesAreCollapsed(): void
    {
        $tag = new Tag();
        $tag->setName("À   retravailler\n plus tard");

        self::assertSame('À retravailler plus tard', $tag->getName());
    }

    public function testATagReadsAsItsName(): void
    {
        $tag = new Tag();
        $tag->setName('Voyage');

        self::assertSame('Voyage', (string) $tag);
    }
}
