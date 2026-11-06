<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Protocol\AuthorizationTransactionRepository;
use Civi\OidcSso\Protocol\StoredAuthorizationTransaction;
use Civi\OidcSso\Protocol\StoredAuthorizationTransactionCodec;
use Throwable;

final class CiviAuthorizationTransactionRepository implements AuthorizationTransactionRepository
{
    private const TABLE = 'civicrm_oidc_sso_transaction';

    public function __construct(private readonly StoredAuthorizationTransactionCodec $codec)
    {
    }

    public function store(string $key, StoredAuthorizationTransaction $transaction): void
    {
        \CRM_Core_DAO::executeQuery(
            'INSERT INTO ' . self::TABLE . ' (state_key, payload, expires_at) VALUES (%1, %2, %3)',
            [
                1 => [$key, 'String'],
                2 => [$this->codec->encode($transaction), 'String'],
                3 => [$transaction->expiresAt, 'Integer'],
            ],
        );
    }

    public function consume(string $key): ?StoredAuthorizationTransaction
    {
        $databaseTransaction = new \CRM_Core_Transaction();
        try {
            $dao = \CRM_Core_DAO::executeQuery(
                'SELECT payload, expires_at FROM ' . self::TABLE . ' WHERE state_key = %1 FOR UPDATE',
                [1 => [$key, 'String']],
            );
            if (!$dao->fetch()) {
                $dao->free();
                $databaseTransaction->commit();
                return null;
            }

            $payload = (string) $dao->payload;
            $expiresAt = (int) $dao->expires_at;
            $dao->free();
            \CRM_Core_DAO::executeQuery(
                'DELETE FROM ' . self::TABLE . ' WHERE state_key = %1',
                [1 => [$key, 'String']],
            );
            $databaseTransaction->commit();
            return $this->codec->decode($payload, $expiresAt);
        } catch (Throwable $exception) {
            $databaseTransaction->rollback();
            throw $exception;
        }
    }

    public function deleteExpired(int $now): int
    {
        $dao = \CRM_Core_DAO::executeQuery(
            'DELETE FROM ' . self::TABLE . ' WHERE expires_at <= %1',
            [1 => [$now, 'Integer']],
        );
        $deleted = $dao->affectedRows();
        $dao->free();
        return $deleted;
    }
}
