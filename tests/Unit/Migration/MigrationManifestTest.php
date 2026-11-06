<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Migration;

use Civi\OidcSso\Migration\MigrationManifest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MigrationManifestTest extends TestCase
{
    public function testParsesStrictManifest(): void
    {
        $manifest = MigrationManifest::fromJson(json_encode([
            'issuer' => 'https://id.example.org/application/o/civicrm/',
            'entries' => [
                ['subject' => 'subject-1', 'user_id' => 20, 'managed_role_ids' => [2, 3]],
            ],
        ], JSON_THROW_ON_ERROR));

        self::assertSame('https://id.example.org/application/o/civicrm/', $manifest->issuer);
        self::assertSame('subject-1', $manifest->entries[0]->subject);
        self::assertSame(20, $manifest->entries[0]->userId);
        self::assertSame([2, 3], $manifest->entries[0]->managedRoleIds);
    }

    /** @dataProvider invalidManifests */
    public function testRejectsAmbiguousOrUnsafeManifest(array $manifest): void
    {
        $this->expectException(InvalidArgumentException::class);
        MigrationManifest::fromJson(json_encode($manifest, JSON_THROW_ON_ERROR));
    }

    public static function invalidManifests(): iterable
    {
        yield 'non-https issuer' => [[
            'issuer' => 'http://id.example.org',
            'entries' => [['subject' => 'a', 'user_id' => 1]],
        ]];
        yield 'duplicate subject' => [[
            'issuer' => 'https://id.example.org',
            'entries' => [
                ['subject' => 'a', 'user_id' => 1],
                ['subject' => 'a', 'user_id' => 2],
            ],
        ]];
        yield 'duplicate user' => [[
            'issuer' => 'https://id.example.org',
            'entries' => [
                ['subject' => 'a', 'user_id' => 1],
                ['subject' => 'b', 'user_id' => 1],
            ],
        ]];
        yield 'duplicate role' => [[
            'issuer' => 'https://id.example.org',
            'entries' => [['subject' => 'a', 'user_id' => 1, 'managed_role_ids' => [2, 2]]],
        ]];
        yield 'unknown field' => [[
            'issuer' => 'https://id.example.org',
            'entries' => [['subject' => 'a', 'user_id' => 1, 'email' => 'ignored@example.org']],
        ]];
    }
}
