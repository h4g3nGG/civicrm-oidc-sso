<?php

declare(strict_types=1);

namespace Civi\OidcSso\Authorization;

interface UserRoleGateway
{
    /** @return list<int> */
    public function get(int $userId): array;

    /** @param list<int> $roleIds */
    public function replace(int $userId, array $roleIds): void;
}
