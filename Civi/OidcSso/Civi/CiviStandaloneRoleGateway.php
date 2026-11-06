<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Authorization\UserRoleGateway;
use RuntimeException;

final class CiviStandaloneRoleGateway implements UserRoleGateway
{
    public function __construct(private readonly Api4Client $api)
    {
    }

    public function get(int $userId): array
    {
        $records = $this->api->call('User', 'get', [
            'select' => ['id', 'roles'],
            'where' => [['id', '=', $userId]],
            'limit' => 1,
        ]);
        if (!isset($records[0])) {
            throw new RuntimeException('CiviCRM user for role synchronization does not exist.');
        }
        $value = $records[0]['roles'] ?? [];
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            $value = explode(',', $value);
        }
        if (!is_array($value)) {
            throw new RuntimeException('CiviCRM returned an invalid role list.');
        }
        $roles = [];
        foreach ($value as $roleId) {
            if (is_int($roleId) || (is_string($roleId) && ctype_digit($roleId))) {
                $roles[(int) $roleId] = (int) $roleId;
            }
        }
        ksort($roles);
        return array_values($roles);
    }

    public function replace(int $userId, array $roleIds): void
    {
        $this->api->call('User', 'update', [
            'where' => [['id', '=', $userId]],
            'values' => ['roles' => array_values(array_unique($roleIds))],
        ]);
    }
}
