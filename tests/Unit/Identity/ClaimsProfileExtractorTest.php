<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Identity;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Identity\ClaimsProfileExtractor;
use PHPUnit\Framework\TestCase;

final class ClaimsProfileExtractorTest extends TestCase
{
    public function testExtractsVerifiedProfile(): void
    {
        $profile = (new ClaimsProfileExtractor())->extract([
            'email' => 'person@example.org',
            'email_verified' => true,
            'preferred_username' => 'person.name',
            'name' => 'Person Name',
            'given_name' => 'Person',
            'family_name' => 'Name',
        ]);

        self::assertSame('person@example.org', $profile->email);
        self::assertSame('person.name', $profile->username);
        self::assertSame('Person', $profile->givenName);
    }

    public function testRejectsUnverifiedEmail(): void
    {
        $this->expectException(OidcValidationException::class);
        (new ClaimsProfileExtractor())->extract([
            'email' => 'person@example.org',
            'email_verified' => false,
            'preferred_username' => 'person',
            'name' => 'Person Name',
            'given_name' => 'Person',
        ]);
    }

    public function testRejectsInvalidUsername(): void
    {
        $this->expectException(OidcValidationException::class);
        (new ClaimsProfileExtractor())->extract([
            'email' => 'person@example.org',
            'email_verified' => true,
            'preferred_username' => '../person',
            'name' => 'Person Name',
            'given_name' => 'Person',
        ]);
    }
}
