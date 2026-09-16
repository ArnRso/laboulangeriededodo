<?php

namespace App\Tests\Unit\Service\Dressing;

use App\Service\Dressing\DressMapping;
use PHPUnit\Framework\TestCase;

class DressMappingTest extends TestCase
{
    public function testASingleSourceAndAListOfSourcesBothBecomeAList(): void
    {
        $mapping = DressMapping::fromFormData(['fields' => [
            'caption' => ['source' => 'title', 'custom' => null],
            'comments' => ['sources' => ['fragment:0', 'custom', 'fragment:0'], 'custom' => 'ta.mere: je suis déçue'],
        ]]);

        self::assertSame(['title'], $mapping->for('caption')->sources);
        self::assertSame('', $mapping->for('caption')->custom);
        self::assertSame(['fragment:0', 'custom'], $mapping->for('comments')->sources, 'Une source n\'est comptée qu\'une fois.');
        self::assertSame('ta.mere: je suis déçue', $mapping->for('comments')->custom);
        self::assertTrue($mapping->usesSource('fragment:0'));
        self::assertFalse($mapping->usesSource('description'));
    }

    public function testAnUnknownFieldLeavesTheDefault(): void
    {
        $mapping = DressMapping::fromFormData(['fields' => ['caption' => ['source' => 'title']]]);

        self::assertSame([], $mapping->for('badge')->sources);
    }

    public function testGarbageIsIgnored(): void
    {
        self::assertSame([], DressMapping::fromFormData('n\'importe quoi')->fields);
        self::assertSame([], DressMapping::fromFormData(['fields' => 'pas un tableau'])->fields);
        self::assertSame([], DressMapping::fromFormData(['fields' => [0 => ['source' => 'title'], 'ok' => 'pas un tableau']])->fields);
        self::assertSame([], DressMapping::fromFormData(['fields' => ['caption' => ['source' => '', 'sources' => [42, ''], 'custom' => 7]]])->for('caption')->sources);
    }
}
