<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Identity\ExternalIdentity;
use Civi\OidcSso\Identity\ExistingAccountLookup;
use Civi\OidcSso\Identity\LocalAccountGateway;
use Civi\OidcSso\Identity\UserProfile;
use RuntimeException;

final class CiviStandaloneAccountGateway implements LocalAccountGateway, ExistingAccountLookup
{
    public function __construct(private readonly Api4Client $api)
    {
    }

    public function create(UserProfile $profile): ExternalIdentity
    {
        $this->assertNoConflicts($profile, null);
        $contact = $this->first($this->api->call('Contact', 'create', [
            'values' => [
                'contact_type' => 'Individual',
                'first_name' => $profile->givenName,
                'last_name' => $profile->familyName,
            ],
        ]), 'contact');
        $contactId = $this->requiredId($contact, 'contact');
        $this->api->call('Email', 'create', [
            'values' => ['contact_id' => $contactId, 'email' => $profile->email, 'is_primary' => true],
        ]);
        $user = $this->first($this->api->call('User', 'create', [
            'values' => [
                'username' => $profile->username,
                'uf_name' => $profile->email,
                'contact_id' => $contactId,
                'password' => $this->randomPassword(),
                'is_active' => true,
            ],
        ]), 'user');
        $userId = $this->requiredId($user, 'user');
        return new ExternalIdentity($contactId, $userId, $userId);
    }

    public function byUserId(int $userId): ExternalIdentity
    {
        $records = $this->api->call('User', 'get', [
            'select' => ['id', 'contact_id'],
            'where' => [['id', '=', $userId]],
            'limit' => 1,
        ]);
        $user = $this->first($records, 'existing user');
        $resolvedUserId = $this->requiredId($user, 'user');
        $contactId = $user['contact_id'] ?? null;
        if (!is_int($contactId) && !(is_string($contactId) && ctype_digit($contactId))) {
            throw new RuntimeException('Existing CiviCRM user has no valid linked contact.');
        }
        return new ExternalIdentity((int) $contactId, $resolvedUserId, $resolvedUserId);
    }

    public function update(ExternalIdentity $identity, UserProfile $profile): void
    {
        $this->assertNoConflicts($profile, $identity);
        $this->api->call('Contact', 'update', [
            'where' => [['id', '=', $identity->contactId]],
            'values' => ['first_name' => $profile->givenName, 'last_name' => $profile->familyName],
        ]);
        $emails = $this->api->call('Email', 'get', [
            'select' => ['id'],
            'where' => [['contact_id', '=', $identity->contactId], ['is_primary', '=', true]],
            'limit' => 1,
        ]);
        if ($emails === []) {
            $this->api->call('Email', 'create', [
                'values' => [
                    'contact_id' => $identity->contactId,
                    'email' => $profile->email,
                    'is_primary' => true,
                ],
            ]);
        } else {
            $emailId = $this->requiredId($emails[0], 'email');
            $this->api->call('Email', 'update', [
                'where' => [['id', '=', $emailId]],
                'values' => ['email' => $profile->email],
            ]);
        }
        $this->api->call('User', 'update', [
            'where' => [['id', '=', $identity->userId]],
            'values' => ['username' => $profile->username, 'uf_name' => $profile->email, 'is_active' => true],
        ]);
    }

    private function assertNoConflicts(UserProfile $profile, ?ExternalIdentity $existing): void
    {
        $this->assertUserFieldAvailable('username', $profile->username, $existing?->userId);
        $this->assertUserFieldAvailable('uf_name', $profile->email, $existing?->userId);
        $where = [['email', '=', $profile->email]];
        if ($existing !== null) {
            $where[] = ['contact_id', '!=', $existing->contactId];
        }
        if ($this->api->call('Email', 'get', ['select' => ['id'], 'where' => $where, 'limit' => 1]) !== []) {
            throw new OidcValidationException('OIDC email address conflicts with an existing local account.');
        }
    }

    private function assertUserFieldAvailable(string $field, string $value, ?int $existingUserId): void
    {
        $where = [[$field, '=', $value]];
        if ($existingUserId !== null) {
            $where[] = ['id', '!=', $existingUserId];
        }
        if ($this->api->call('User', 'get', ['select' => ['id'], 'where' => $where, 'limit' => 1]) !== []) {
            throw new OidcValidationException("OIDC {$field} conflicts with an existing local account.");
        }
    }

    /** @param list<array<string, mixed>> $records @return array<string, mixed> */
    private function first(array $records, string $description): array
    {
        if (!isset($records[0])) {
            throw new RuntimeException("CiviCRM did not return the created {$description}.");
        }
        return $records[0];
    }

    /** @param array<string, mixed> $record */
    private function requiredId(array $record, string $description): int
    {
        $id = $record['id'] ?? null;
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            throw new RuntimeException("CiviCRM returned an invalid {$description} ID.");
        }
        return (int) $id;
    }

    private function randomPassword(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
    }
}
