<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Civi;

use Civi\OidcSso\Civi\SettingsValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SettingsValidatorTest extends TestCase
{
    #[DataProvider('issuerProvider')]
    public function testIssuerValidation(mixed $value, bool $expected): void
    {
        self::assertSame($expected, SettingsValidator::issuer($value));
    }

    /** @return iterable<string, array{mixed, bool}> */
    public static function issuerProvider(): iterable
    {
        yield 'unconfigured' => ['', true];
        yield 'HTTPS issuer' => ['https://id.example.org/application/o/civicrm/', true];
        yield 'HTTP issuer' => ['http://id.example.org/', false];
        yield 'query' => ['https://id.example.org/?tenant=1', false];
        yield 'credentials' => ['https://user:pass@id.example.org/', false];
    }

    public function testScopesRequireOpenid(): void
    {
        self::assertTrue(SettingsValidator::scopes('openid profile email groups'));
        self::assertFalse(SettingsValidator::scopes('profile email'));
        self::assertFalse(SettingsValidator::scopes("openid\nprofile"));
    }

    public function testGroupClaimUsesSafeIdentifier(): void
    {
        self::assertTrue(SettingsValidator::groupClaim('realm_access.groups'));
        self::assertFalse(SettingsValidator::groupClaim('groups[0]'));
    }

    public function testRoleMappingRequiresPositiveIntegerRoleIds(): void
    {
        self::assertTrue(SettingsValidator::roleMapping('{"group.one":[2,5]}'));
        self::assertTrue(SettingsValidator::roleMapping('{}'));
        self::assertFalse(SettingsValidator::roleMapping('{"group.one":["admin"]}'));
        self::assertFalse(SettingsValidator::roleMapping('[]'));
        self::assertFalse(SettingsValidator::roleMapping('invalid'));
    }
}
