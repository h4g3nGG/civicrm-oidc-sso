<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Authorization;

use Civi\OidcSso\Authorization\ClaimGroupExtractor;
use Civi\OidcSso\Authorization\ClaimRoleSynchronizer;
use Civi\OidcSso\Authorization\ManagedRoleRepository;
use Civi\OidcSso\Authorization\RoleMapper;
use Civi\OidcSso\Authorization\RoleReconciler;
use Civi\OidcSso\Authorization\UserRoleGateway;
use Civi\OidcSso\Exception\OidcValidationException;
use PHPUnit\Framework\TestCase;

final class ClaimRoleSynchronizerTest extends TestCase
{
    public function testMapsExactGroupsToRoles(): void
    {
        $roles = $this->createMock(UserRoleGateway::class);
        $managed = $this->createMock(ManagedRoleRepository::class);
        $roles->method('get')->willReturn([]);
        $managed->method('get')->willReturn([]);
        $roles->expects(self::once())->method('replace')->with(10, [2, 5]);
        $managed->expects(self::once())->method('replace')->with(10, [2, 5]);

        (new ClaimRoleSynchronizer(
            new ClaimGroupExtractor('groups'),
            new RoleMapper(['group.staff' => [2], 'group.admin' => [5]]),
            new RoleReconciler($roles, $managed),
        ))->synchronize(10, ['groups' => ['group.staff', 'group.admin']]);
    }

    public function testRemovesManagedRolesThenRejectsUnmappedIdentity(): void
    {
        $roles = $this->createMock(UserRoleGateway::class);
        $managed = $this->createMock(ManagedRoleRepository::class);
        $roles->method('get')->willReturn([1, 2]);
        $managed->method('get')->willReturn([2]);
        $roles->expects(self::once())->method('replace')->with(10, [1]);
        $managed->expects(self::once())->method('replace')->with(10, []);

        $this->expectException(OidcValidationException::class);
        (new ClaimRoleSynchronizer(
            new ClaimGroupExtractor('groups'),
            new RoleMapper([]),
            new RoleReconciler($roles, $managed),
        ))->synchronize(10, ['groups' => ['unknown']]);
    }
}
