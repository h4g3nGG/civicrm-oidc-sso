<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Audit\AuditDetails;
use Civi\OidcSso\Audit\AuditEventWriter;
use InvalidArgumentException;

final class CiviAuditEventWriter implements AuditEventWriter
{
    private const ZERO_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    public function __construct(private readonly string $key)
    {
        if (strlen($key) < 32) {
            throw new InvalidArgumentException('Audit HMAC key must contain at least 32 bytes.');
        }
    }

    public function append(string $eventType, string $outcome, ?int $userId = null, array $details = []): void
    {
        $this->assertCode($eventType, 'event type');
        $this->assertCode($outcome, 'outcome');
        if ($userId !== null && $userId < 1) {
            throw new InvalidArgumentException('Audit user ID must be positive.');
        }
        $detailJson = AuditDetails::encode($details);
        $eventId = bin2hex(random_bytes(16));
        $occurredAt = gmdate('Y-m-d H:i:s');
        $transaction = new \CRM_Core_Transaction();
        try {
            \CRM_Core_DAO::executeQuery(
                'INSERT IGNORE INTO civicrm_oidc_sso_audit_head (id, last_hash) VALUES (1, %1)',
                [1 => [self::ZERO_HASH, 'String']],
            );
            $previousHash = (string) \CRM_Core_DAO::singleValueQuery(
                'SELECT last_hash FROM civicrm_oidc_sso_audit_head WHERE id = 1 FOR UPDATE',
            );
            $canonical = implode("\n", [
                $previousHash, $eventId, $occurredAt, $eventType, $outcome,
                $userId === null ? '' : (string) $userId, $detailJson,
            ]);
            $eventHash = hash_hmac('sha256', $canonical, $this->key);
            \CRM_Core_DAO::executeQuery(
                'INSERT INTO civicrm_oidc_sso_audit_event'
                . ' (event_id, occurred_at, event_type, outcome, uf_match_id, details_json, previous_hash, event_hash)'
                . ' VALUES (%1, %2, %3, %4, %5, %6, %7, %8)',
                [
                    1 => [$eventId, 'String'], 2 => [$occurredAt, 'String'],
                    3 => [$eventType, 'String'], 4 => [$outcome, 'String'],
                    5 => [$userId, 'Integer'], 6 => [$detailJson, 'String'],
                    7 => [$previousHash, 'String'], 8 => [$eventHash, 'String'],
                ],
            );
            \CRM_Core_DAO::executeQuery(
                'UPDATE civicrm_oidc_sso_audit_head SET last_hash = %1 WHERE id = 1',
                [1 => [$eventHash, 'String']],
            );
            $transaction->commit();
        } catch (\Throwable $exception) {
            $transaction->rollback();
            throw $exception;
        }
    }

    private function assertCode(string $value, string $description): void
    {
        if (preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $value) !== 1) {
            throw new InvalidArgumentException("Audit {$description} is not a controlled code.");
        }
    }
}
