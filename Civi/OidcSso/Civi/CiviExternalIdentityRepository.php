<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Identity\ExternalIdentity;
use Civi\OidcSso\Identity\ExternalIdentityRepository;
use RuntimeException;

final class CiviExternalIdentityRepository implements ExternalIdentityRepository
{
    private const TABLE = 'civicrm_oidc_sso_identity';

    public function find(string $issuer, string $subject): ?ExternalIdentity
    {
        $dao = \CRM_Core_DAO::executeQuery(
            'SELECT issuer, subject, contact_id, uf_match_id FROM ' . self::TABLE
            . ' WHERE issuer_hash = %1 AND subject_hash = %2',
            [
                1 => [$this->hash($issuer), 'String'],
                2 => [$this->hash($subject), 'String'],
            ],
        );
        if (!$dao->fetch()) {
            $dao->free();
            return null;
        }
        if (!hash_equals((string) $dao->issuer, $issuer) || !hash_equals((string) $dao->subject, $subject)) {
            $dao->free();
            throw new RuntimeException('OIDC external identity hash collision detected.');
        }
        $identity = new ExternalIdentity((int) $dao->contact_id, (int) $dao->uf_match_id, (int) $dao->uf_match_id);
        $dao->free();
        return $identity;
    }

    public function create(string $issuer, string $subject, ExternalIdentity $identity): void
    {
        \CRM_Core_DAO::executeQuery(
            'INSERT INTO ' . self::TABLE
            . ' (issuer_hash, subject_hash, issuer, subject, contact_id, uf_match_id)'
            . ' VALUES (%1, %2, %3, %4, %5, %6)',
            [
                1 => [$this->hash($issuer), 'String'],
                2 => [$this->hash($subject), 'String'],
                3 => [$issuer, 'String'],
                4 => [$subject, 'String'],
                5 => [$identity->contactId, 'Integer'],
                6 => [$identity->ufMatchId, 'Integer'],
            ],
        );
    }

    private function hash(string $value): string
    {
        return hash('sha256', $value);
    }
}
