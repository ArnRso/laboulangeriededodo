<?php

namespace App\Tests\Unit\Service\Dressing;

use App\Enum\AppFieldKind;
use App\Service\Dressing\AppDataComposer;
use App\Service\Dressing\AppField;
use App\Service\Dressing\DressMapping;
use App\Service\Dressing\Source;
use PHPUnit\Framework\TestCase;

class AppDataComposerTest extends TestCase
{
    private AppDataComposer $composer;

    protected function setUp(): void
    {
        $this->composer = new AppDataComposer();
    }

    public function testAnUnmappedFieldKeepsTheAppDefault(): void
    {
        $composed = $this->composer->compose([self::field('username', default: 'dodo.du.passe')], self::sources(), DressMapping::empty());

        self::assertSame(['username' => 'dodo.du.passe'], $composed);
    }

    public function testTheTitleCanLandInAnyTextField(): void
    {
        $composed = $this->composer->compose(
            [self::field('comment', default: '')],
            self::sources(),
            self::mapping(['comment' => ['source' => 'title']]),
        );

        self::assertSame(['comment' => 'Le message de 4 h 12'], $composed);
    }

    public function testSeveralSourcesAreJoinedWithLineBreaksInCatalogueOrder(): void
    {
        $composed = $this->composer->compose(
            [self::field('comments', AppFieldKind::TEXTAREA, '')],
            self::sources(),
            self::mapping(['comments' => ['sources' => ['fragment:0', 'title', 'description']]]),
        );

        self::assertSame(['comments' => "Le message de 4 h 12\nTu avais dit une heure.\nmarie: j'étais là"], $composed);
    }

    public function testFreeTextIsUsedOnlyWhenSelected(): void
    {
        $fields = [self::field('badge', default: '')];

        $ignored = $this->composer->compose($fields, self::sources(), self::mapping(['badge' => ['source' => 'title', 'custom' => 'Icon']]));
        $used = $this->composer->compose($fields, self::sources(), self::mapping(['badge' => ['source' => 'custom', 'custom' => 'Icon']]));

        self::assertSame('Le message de 4 h 12', $ignored['badge']);
        self::assertSame('Icon', $used['badge']);
    }

    public function testAnUnknownOrEmptySourceFallsBackToTheDefault(): void
    {
        $fields = [self::field('location', default: 'Nulle part')];

        $unknown = $this->composer->compose($fields, self::sources(), self::mapping(['location' => ['source' => 'fragment:9']]));
        $empty = $this->composer->compose($fields, self::sources(), self::mapping(['location' => ['source' => 'custom', 'custom' => '   ']]));

        self::assertSame('Nulle part', $unknown['location']);
        self::assertSame('Nulle part', $empty['location']);
    }

    public function testAnIntegerFieldConvertsDigitsAndRefusesTheRest(): void
    {
        $fields = [self::field('likesCount', AppFieldKind::INTEGER, 1240)];

        $digits = $this->composer->compose($fields, self::sources(), self::mapping(['likesCount' => ['source' => 'custom', 'custom' => ' 42 ']]));
        $words = $this->composer->compose($fields, self::sources(), self::mapping(['likesCount' => ['source' => 'title']]));

        self::assertSame(42, $digits['likesCount']);
        self::assertSame(1240, $words['likesCount']);
    }

    public function testACheckboxUnderstandsOuiAndNon(): void
    {
        $fields = [self::field('topTen', AppFieldKind::CHECKBOX, false)];

        $yes = $this->composer->compose($fields, self::sources(), self::mapping(['topTen' => ['source' => 'custom', 'custom' => 'Oui']]));
        $no = $this->composer->compose($fields, self::sources(), self::mapping(['topTen' => ['source' => 'custom', 'custom' => 'non']]));
        $unclear = $this->composer->compose($fields, self::sources(), self::mapping(['topTen' => ['source' => 'title']]));

        self::assertTrue($yes['topTen']);
        self::assertFalse($no['topTen']);
        self::assertFalse($unclear['topTen']);
    }

    public function testAChoiceFieldOnlyAcceptsItsOwnValues(): void
    {
        $fields = [self::field('stars', AppFieldKind::CHOICE, 5, ['★' => 1, '★★' => 2, '★★★' => 3])];

        $byValue = $this->composer->compose($fields, self::sources(), self::mapping(['stars' => ['source' => 'custom', 'custom' => '2']]));
        $byLabel = $this->composer->compose($fields, self::sources(), self::mapping(['stars' => ['source' => 'custom', 'custom' => '★★★']]));
        $outside = $this->composer->compose($fields, self::sources(), self::mapping(['stars' => ['source' => 'custom', 'custom' => '9']]));

        self::assertSame(2, $byValue['stars']);
        self::assertSame(3, $byLabel['stars']);
        self::assertSame(5, $outside['stars']);
    }

    public function testASingleLineFieldIgnoresExtraSources(): void
    {
        $composed = $this->composer->compose(
            [self::field('caption', default: '')],
            self::sources(),
            self::mapping(['caption' => ['sources' => ['description', 'title']]]),
        );

        self::assertSame('Le message de 4 h 12', $composed['caption'], 'La première source du catalogue l\'emporte.');
    }

    public function testTheResultHasExactlyTheFieldsInTheirOrder(): void
    {
        $composed = $this->composer->compose(
            [self::field('b', default: 'B'), self::field('a', default: 'A')],
            self::sources(),
            self::mapping(['zzz' => ['source' => 'title']]),
        );

        self::assertSame(['b' => 'B', 'a' => 'A'], $composed);
    }

    /**
     * @param array<string, int|string> $choices
     */
    private static function field(string $name, AppFieldKind $kind = AppFieldKind::TEXT, mixed $default = '', array $choices = []): AppField
    {
        return new AppField($name, ucfirst($name), '', $kind, false, $default, $choices);
    }

    /**
     * @return list<Source>
     */
    private static function sources(): array
    {
        return [
            new Source('title', 'Titre', 'Le message de 4 h 12'),
            new Source('description', 'Description', 'Tu avais dit une heure.'),
            new Source('fragment:0', 'Fragment · Marie', "marie: j'étais là"),
            new Source('custom', 'Texte libre', ''),
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $fields
     */
    private static function mapping(array $fields): DressMapping
    {
        return DressMapping::fromFormData(['fields' => $fields]);
    }
}
