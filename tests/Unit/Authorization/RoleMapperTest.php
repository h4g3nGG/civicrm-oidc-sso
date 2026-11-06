<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Authorization;

use Civi\OidcSso\Authorization\RoleMapper;
use PHPUnit\Framework\TestCase;

final class RoleMapperTest extends TestCase
{
    public function testOnlyExplicitlyMappedGroupsProduceRoles(): void
    {
        $mapper = new RoleMapper([
            'app.example.staff' => [2],
            'app.example.admin' => [2, 5],
        ]);

        self::assertSame(
            [2, 5],
            $mapper->rolesForGroups([
                'app.example.staff',
                'unknown.external.group',
                'app.example.admin',
            ])
        );
    }

    public function testMalformedClaimsAreIgnored(): void
    {
        $mapper = new RoleMapper(['app.example.staff' => ['staff']]);

        self::assertSame(
            ['staff'],
            $mapper->rolesForGroups([NULL, 123, ['nested'], 'app.example.staff'])
        );
    }
}

