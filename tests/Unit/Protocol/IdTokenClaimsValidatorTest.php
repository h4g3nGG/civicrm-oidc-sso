<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\IdTokenClaimsValidator;
use PHPUnit\Framework\TestCase;

final class IdTokenClaimsValidatorTest extends TestCase
{
    private const NOW = 1_700_000_000;

    public function testAcceptsValidClaims(): void
    {
        $this->validator()->validate($this->validClaims(), 'expected-nonce', self::NOW);
        self::addToAssertionCount(1);
    }

    /** @dataProvider invalidClaims */
    public function testRejectsInvalidClaims(array $changes): void
    {
        $this->expectException(OidcValidationException::class);
        $this->validator()->validate(array_replace($this->validClaims(), $changes), 'expected-nonce', self::NOW);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidClaims(): iterable
    {
        yield 'issuer' => [['iss' => 'https://attacker.example/']];
        yield 'audience' => [['aud' => 'different-client']];
        yield 'nonce' => [['nonce' => 'different-nonce']];
        yield 'expired' => [['exp' => self::NOW - 61]];
        yield 'issued in future' => [['iat' => self::NOW + 61]];
        yield 'too old' => [['iat' => self::NOW - 962]];
        yield 'not valid yet' => [['nbf' => self::NOW + 61]];
        yield 'multiple audience without azp' => [['aud' => ['civicrm-client', 'other']]];
        yield 'wrong azp' => [['aud' => ['civicrm-client', 'other'], 'azp' => 'other']];
        yield 'empty subject' => [['sub' => '']];
        yield 'numeric expiry' => [['exp' => (string) (self::NOW + 300)]];
    }

    private function validator(): IdTokenClaimsValidator
    {
        return new IdTokenClaimsValidator('https://id.example.org/', 'civicrm-client');
    }

    /** @return array<string, mixed> */
    private function validClaims(): array
    {
        return [
            'iss' => 'https://id.example.org/',
            'sub' => '248289761001',
            'aud' => 'civicrm-client',
            'exp' => self::NOW + 300,
            'iat' => self::NOW,
            'nonce' => 'expected-nonce',
        ];
    }
}
