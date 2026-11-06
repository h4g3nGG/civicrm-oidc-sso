<?php

declare(strict_types=1);

namespace Civi\OidcSso\Authorization;

/**
 * Maps trusted group claims to an explicit allow list of local role IDs.
 */
final class RoleMapper
{
    /**
     * @param array<string, list<int|string>> $mapping
     */
    public function __construct(private readonly array $mapping)
    {
    }

    /**
     * @param list<mixed> $groups
     * @return list<int|string>
     */
    public function rolesForGroups(array $groups): array
    {
        $roles = [];
        foreach ($groups as $group) {
            if (!is_string($group) || !array_key_exists($group, $this->mapping)) {
                continue;
            }
            foreach ($this->mapping[$group] as $role) {
                $roles[(string) $role] = $role;
            }
        }

        return array_values($roles);
    }
}

