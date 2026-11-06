<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Authorization;

use Civi\OidcSso\Authorization\ManagedRoleRepository;
use Civi\OidcSso\Authorization\RoleOwnershipMigrator;
use Civi\OidcSso\Authorization\UserRoleGateway;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RoleOwnershipMigratorTest extends TestCase
{
    public function testClaimsOnlyApprovedExistingRoles(): void
    {
        $managed = $this->managed([4]);
        (new RoleOwnershipMigrator($this->roles([2, 3, 4]), $managed))->claimExisting(10, [3, 2]);

        self::assertSame([2, 3, 4], $managed->written);
    }

    public function testRefusesRoleNotCurrentlyAssigned(): void
    {
        $managed = $this->managed([]);

        $this->expectException(InvalidArgumentException::class);
        (new RoleOwnershipMigrator($this->roles([3]), $managed))->claimExisting(10, [2]);
    }

    private function roles(array $current): UserRoleGateway
    {
        return new class ($current) implements UserRoleGateway {
            public function __construct(private readonly array $current)
            {
            }

            public function get(int $userId): array
            {
                return $this->current;
            }

            public function replace(int $userId, array $roleIds): void
            {
                throw new \LogicException('Migration must not alter current roles.');
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
