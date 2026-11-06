<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Authorization;

use Civi\OidcSso\Authorization\ManagedRoleRepository;
use Civi\OidcSso\Authorization\RoleReconciler;
use Civi\OidcSso\Authorization\UserRoleGateway;
use PHPUnit\Framework\TestCase;

final class RoleReconcilerTest extends TestCase
{
    public function testRemovesOnlyPreviouslyManagedRoles(): void
    {
        $roles = $this->roles([1, 2, 3]);
        $managed = $this->managed([2]);

        (new RoleReconciler($roles, $managed))->reconcile(10, [3, 4]);

        self::assertSame([1, 3, 4], $roles->written);
        self::assertSame([4], $managed->written);
    }

    public function testPreexistingDesiredRoleRemainsManual(): void
    {
        $roles = $this->roles([1, 3]);
        $managed = $this->managed([]);

        (new RoleReconciler($roles, $managed))->reconcile(10, [3]);

        self::assertSame([1, 3], $roles->written);
        self::assertSame([], $managed->written);
    }

    private function roles(array $current): UserRoleGateway
    {
        return new class ($current) implements UserRoleGateway {
            public array $written = [];

            public function __construct(private readonly array $current)
            {
            }

            public function get(int $userId): array
            {
                return $this->current;
            }

            public function replace(int $userId, array $roleIds): void
            {
                $this->written = $roleIds;
            }
        };
    }

    private function managed(array $current): ManagedRoleRepository
    {
        return new class ($current) implements ManagedRoleRepository {
            public array $written = [];

            public function __construct(private readonly array $current)
            {
            }

            public function get(int $userId): array
            {
                return $this->current;
            }

            public function replace(int $userId, array $roleIds): void
            {
                $this->written = $roleIds;
            }
        };
    }
}
