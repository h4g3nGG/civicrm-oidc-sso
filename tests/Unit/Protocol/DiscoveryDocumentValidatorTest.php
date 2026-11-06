<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\DiscoveryDocumentValidator;
use PHPUnit\Framework\TestCase;

final class DiscoveryDocumentValidatorTest extends TestCase
{
    public function testAcceptsSecureProviderMetadata(): void
    {
        $metadata = (new DiscoveryDocumentValidator('https://id.example.org/application/o/crm/'))
            ->validate($this->validDocument());

        self::assertSame('https://id.example.org/application/o/crm/', $metadata->issuer);
        self::assertSame(['RS256'], $metadata->idTokenSigningAlgorithms);
    }

    /** @dataProvider invalidDocuments */
    public function testRejectsInvalidMetadata(array $changes): void
    {
        $this->expectException(OidcValidationException::class);
        (new DiscoveryDocumentValidator('https://id.example.org/application/o/crm/'))
            ->validate(array_replace($this->validDocument(), $changes));
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidDocuments(): iterable
    {
        yield 'issuer mismatch' => [['issuer' => 'https://id.example.org/application/o/other/']];
        yield 'insecure authorization endpoint' => [['authorization_endpoint' => 'http://id.example.org/auth']];
        yield 'credentials in endpoint' => [['token_endpoint' => 'https://user:pass@id.example.org/token']];
        yield 'fragment in jwks URL' => [['jwks_uri' => 'https://id.example.org/jwks#keys']];
        yield 'missing code flow' => [['response_types_supported' => ['id_token']]];
        yield 'missing PKCE S256' => [['code_challenge_methods_supported' => ['plain']]];
        yield 'unsigned tokens only' => [['id_token_signing_alg_values_supported' => ['none']]];
    }

    /** @return array<string, mixed> */
    private function validDocument(): array
    {
        return [
            'issuer' => 'https://id.example.org/application/o/crm/',
            'authorization_endpoint' => 'https://id.example.org/application/o/authorize/',
            'token_endpoint' => 'https://id.example.org/application/o/token/',
            'jwks_uri' => 'https://id.example.org/application/o/crm/jwks/',
            'response_types_supported' => ['code'],
            'code_challenge_methods_supported' => ['S256'],
            'id_token_signing_alg_values_supported' => ['RS256', 'none'],
        ];
    }
}
