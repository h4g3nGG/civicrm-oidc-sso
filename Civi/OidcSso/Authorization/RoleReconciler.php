<?php

declare(strict_types=1);

namespace Civi\OidcSso\Authorization;

final class RoleReconciler
{
    public function __construct(
        private readonly UserRoleGateway $roles,
        private readonly ManagedRoleRepository $managed,
    ) {
    }

    /** @param list<int> $desiredRoleIds */
    public function reconcile(int $userId, array $desiredRoleIds): void
    {
        $current = $this->set($this->roles->get($userId));
        $previouslyManaged = $this->set($this->managed->get($userId));
        $desired = $this->set($desiredRoleIds);

        $rolesToRemove = array_diff_key($previouslyManaged, $desired);
        $result = array_diff_key($current, $rolesToRemove);
        $newlyAdded = array_diff_key($desired, $current);
        $result += $desired;

        // A role which was already present before OIDC requested it remains
        // manual/unowned. Only roles actually added by this extension, plus
        // its still-desired prior assignments, are recorded as managed.
        $stillManaged = array_intersect_key($previouslyManaged, $desired);
        $managedResult = $stillManaged + $newlyAdded;

        $this->roles->replace($userId, array_values($result));
        $this->managed->replace($userId, array_values($managedResult));
    }

    /** @param list<int> $values @return array<int, int> */
    private function set(array $values): array
    {
        $result = [];
        foreach ($values as $value) {
            if ($value > 0) {
                $result[$value] = $value;
            }
        }
        ksort($result);
        return $result;
    }
}
