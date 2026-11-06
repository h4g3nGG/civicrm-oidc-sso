<?php

declare(strict_types=1);

namespace Civi\OidcSso\Migration;

use InvalidArgumentException;
use JsonException;

final class MigrationManifest
{
    /** @param list<MigrationEntry> $entries */
    private function __construct(
        public readonly string $issuer,
        public readonly array $entries,
    ) {
    }

    public static function fromJson(string $json): self
    {
        try {
            $data = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Migration manifest is not valid JSON.', 0, $exception);
        }
        if (!is_array($data) || array_is_list($data)) {
            throw new InvalidArgumentException('Migration manifest must be a JSON object.');
        }
        self::rejectUnknownKeys($data, ['issuer', 'entries'], 'manifest');
        $issuer = $data['issuer'] ?? null;
        if (!is_string($issuer) || !self::isHttpsIssuer($issuer)) {
            throw new InvalidArgumentException('Migration issuer must be an exact HTTPS issuer URL.');
        }
        $rawEntries = $data['entries'] ?? null;
        if (!is_array($rawEntries) || $rawEntries === [] || !array_is_list($rawEntries)) {
            throw new InvalidArgumentException('Migration entries must be a non-empty JSON array.');
        }

        $entries = [];
        $subjects = [];
        $users = [];
        foreach ($rawEntries as $index => $rawEntry) {
            if (!is_array($rawEntry) || array_is_list($rawEntry)) {
                throw new InvalidArgumentException("Migration entry {$index} must be a JSON object.");
            }
            self::rejectUnknownKeys($rawEntry, ['subject', 'user_id', 'managed_role_ids'], "entry {$index}");
            $subject = $rawEntry['subject'] ?? null;
            $userId = $rawEntry['user_id'] ?? null;
            $roleIds = $rawEntry['managed_role_ids'] ?? [];
            if (!is_string($subject) || $subject === '' || strlen($subject) > 255) {
                throw new InvalidArgumentException("Migration entry {$index} has an invalid subject.");
            }
            if (!is_int($userId) || $userId < 1) {
                throw new InvalidArgumentException("Migration entry {$index} has an invalid user_id.");
            }
            if (!is_array($roleIds) || !array_is_list($roleIds)) {
                throw new InvalidArgumentException("Migration entry {$index} has invalid managed_role_ids.");
            }
            $roles = [];
            foreach ($roleIds as $roleId) {
                if (!is_int($roleId) || $roleId < 1 || isset($roles[$roleId])) {
                    throw new InvalidArgumentException("Migration entry {$index} has an invalid or duplicate role ID.");
                }
                $roles[$roleId] = $roleId;
            }
            if (isset($subjects[$subject])) {
                throw new InvalidArgumentException("Migration subject is duplicated at entry {$index}.");
            }
            if (isset($users[$userId])) {
                throw new InvalidArgumentException("Migration user_id is duplicated at entry {$index}.");
            }
            $subjects[$subject] = true;
            $users[$userId] = true;
            $entries[] = new MigrationEntry($subject, $userId, array_values($roles));
        }
        return new self($issuer, $entries);
    }

    /** @param array<string, mixed> $data @param list<string> $allowed */
    private static function rejectUnknownKeys(array $data, array $allowed, string $location): void
    {
        $unknown = array_diff(array_keys($data), $allowed);
        if ($unknown !== []) {
            throw new InvalidArgumentException("Unknown field in migration {$location}: " . reset($unknown));
        }
    }

    private static function isHttpsIssuer(string $issuer): bool
    {
        $parts = parse_url($issuer);
        return $parts !== false
            && ($parts['scheme'] ?? '') === 'https'
            && !empty($parts['host'])
            && !isset($parts['user'], $parts['pass'], $parts['query'], $parts['fragment']);
    }
}
