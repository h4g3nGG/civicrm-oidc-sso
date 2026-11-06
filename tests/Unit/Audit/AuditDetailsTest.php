<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Audit;

use Civi\OidcSso\Audit\AuditDetails;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuditDetailsTest extends TestCase
{
    public function testProducesCanonicalAllowListedJson(): void
    {
        self::assertSame(
            '{"added_count":2,"reason_code":"role_changed"}',
            AuditDetails::encode(['reason_code' => 'role_changed', 'added_count' => 2]),
        );
    }

    public function testRejectsUnknownField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AuditDetails::encode(['email' => 'person_example_org']);
    }

    public function testRejectsFreeFormText(): void
    {
        $this->expectException(InvalidArgumentException::class);
        AuditDetails::encode(['reason_code' => 'Exception: person@example.org']);
    }
}
