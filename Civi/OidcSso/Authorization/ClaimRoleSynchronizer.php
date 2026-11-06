<?php

declare(strict_types=1);

namespace Civi\OidcSso\Authorization;

use Civi\OidcSso\Exception\OidcValidationException;
use InvalidArgumentException;

final class ClaimRoleSynchronizer
{
    public function __construct(
        private readonly ClaimGroupExtractor $groups,
        private readonly RoleMapper $mapper,
        private readonly RoleReconciler $reconciler,
    ) {
    }

    /** @param array<string, mixed> $claims */
    public function synchronize(int $userId, array $claims): void
    {
        $mapped = $this->mapper->rolesForGroups($this->groups->extract($claims));
        $roleIds = [];
        foreach ($mapped as $roleId) {
            if (!is_int($roleId) || $roleId < 1) {
                throw new InvalidArgumentException('Standalone role mapping must contain positive integer IDs.');
            }
            $roleIds[] = $roleId;
        }
        $this->reconciler->reconcile($userId, $roleIds);
        if ($roleIds === []) {
            throw new OidcValidationException('OIDC identity has no explicitly mapped local role.');
        }
    }
}
