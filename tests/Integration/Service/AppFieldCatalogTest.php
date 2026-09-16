<?php

namespace App\Tests\Integration\Service;

use App\Enum\AppFieldKind;
use App\Enum\AppKind;
use App\Service\Dressing\AppFieldCatalog;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AppFieldCatalogTest extends KernelTestCase
{
    private AppFieldCatalog $catalog;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->catalog = self::getContainer()->get(AppFieldCatalog::class);
    }

    public function testEveryKindOfFieldIsRecognised(): void
    {
        $kinds = [];

        foreach ($this->catalog->fieldsFor(AppKind::NETFLIX) as $field) {
            $kinds[$field->name] = $field->kind;
        }

        self::assertSame(AppFieldKind::TEXT, $kinds['showTitle']);
        self::assertSame(AppFieldKind::TEXTAREA, $kinds['synopsis']);
        self::assertSame(AppFieldKind::INTEGER, $kinds['match']);
        self::assertSame(AppFieldKind::CHECKBOX, $kinds['topTen']);

        $stars = $this->catalog->field(AppKind::UBER_EATS, 'stars');
        self::assertNotNull($stars);
        self::assertSame(AppFieldKind::CHOICE, $stars->kind);
        self::assertSame([1, 2, 3, 4, 5], array_values($stars->choices));
    }

    public function testLabelHelpRequirementAndDefaultAreCarried(): void
    {
        $username = $this->catalog->field(AppKind::INSTAGRAM, 'username');
        $comments = $this->catalog->field(AppKind::INSTAGRAM, 'comments');

        self::assertNotNull($username);
        self::assertNotNull($comments);
        self::assertSame('Compte qui publie', $username->label);
        self::assertTrue($username->required);
        self::assertSame('dodo.du.passe', $username->default);
        self::assertFalse($comments->required);
        self::assertStringContainsString('Un par ligne', $comments->help);
    }

    public function testAnUnknownFieldIsNull(): void
    {
        self::assertNull($this->catalog->field(AppKind::INSTAGRAM, 'nope'));
    }
}
