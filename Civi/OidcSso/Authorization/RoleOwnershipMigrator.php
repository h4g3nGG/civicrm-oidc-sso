<?php

declare(strict_types=1);

namespace Civi\OidcSso\Authorization;

use InvalidArgumentException;

final class RoleOwnershipMigrator
{
    public function __construct(
        private readonly UserRoleGateway $roles,
        private readonly ManagedRoleRepository $managed,
    ) {
    }

    /** @param list<int> $roleIds */
    public function claimExisting(int $userId, array $roleIds): void
    {
        if ($userId < 1) {
            throw new InvalidArgumentException('CiviCRM user ID must be positive.');
        }

        $current = $this->set($this->roles->get($userId));
        $requested = $this->set($roleIds);
        if (array_diff_key($requested, $current) !== []) {
            throw new InvalidArgumentException('Only roles currently assigned to the user can become OIDC-managed.');
        }

        $managed = $this->set($this->managed->get($userId));
        $managed += $requested;
        ksort($managed);
        $this->managed->replace($userId, array_values($managed));
    }

    /** @param list<int> $values @return array<int, int> */
    private function set(array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            if (!is_int($value) || $value < 1) {
                throw new InvalidArgumentException('Role IDs must be positive integers.');
            }
            $result[$value] = $value;
        }
        ksort($result);
        return $result;
    }
}
