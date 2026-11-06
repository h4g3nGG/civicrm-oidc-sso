<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Authorization\ManagedRoleRepository;
use Throwable;

final class CiviManagedRoleRepository implements ManagedRoleRepository
{
    private const TABLE = 'civicrm_oidc_sso_managed_role';

    public function get(int $userId): array
    {
        $dao = \CRM_Core_DAO::executeQuery(
            'SELECT role_id FROM ' . self::TABLE . ' WHERE uf_match_id = %1 ORDER BY role_id',
            [1 => [$userId, 'Integer']],
        );
        $roles = [];
        while ($dao->fetch()) {
            $roles[] = (int) $dao->role_id;
        }
        $dao->free();
        return $roles;
    }

    public function replace(int $userId, array $roleIds): void
    {
        $transaction = new \CRM_Core_Transaction();
        try {
            \CRM_Core_DAO::executeQuery(
                'DELETE FROM ' . self::TABLE . ' WHERE uf_match_id = %1',
                [1 => [$userId, 'Integer']],
            );
            foreach (array_values(array_unique($roleIds)) as $roleId) {
                \CRM_Core_DAO::executeQuery(
                    'INSERT INTO ' . self::TABLE . ' (uf_match_id, role_id) VALUES (%1, %2)',
                    [1 => [$userId, 'Integer'], 2 => [$roleId, 'Integer']],
                );
            }
            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollback();
            throw $exception;
        }
    }
}
