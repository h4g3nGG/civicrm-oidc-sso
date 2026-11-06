<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Authorization;

use Civi\OidcSso\Authorization\ClaimGroupExtractor;
use Civi\OidcSso\Exception\OidcValidationException;
use PHPUnit\Framework\TestCase;

final class ClaimGroupExtractorTest extends TestCase
{
    public function testExtractsNestedUniqueGroupStrings(): void
    {
        self::assertSame(
            ['group.one', 'group.two'],
            (new ClaimGroupExtractor('custom.groups'))->extract([
                'custom' => ['groups' => ['group.one', 'group.two', 'group.one']],
            ]),
        );
    }

    public function testMissingClaimMeansNoGroups(): void
    {
        self::assertSame([], (new ClaimGroupExtractor('groups'))->extract([]));
    }

    public function testRejectsScalarGroupClaim(): void
    {
        $this->expectException(OidcValidationException::class);
        (new ClaimGroupExtractor('groups'))->extract(['groups' => 'group.one']);
    }
}
