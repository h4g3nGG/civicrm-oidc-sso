<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Exception\UnknownSigningKeyException;
use Civi\OidcSso\Protocol\JwtSignatureVerifier;
use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;

final class JwtSignatureVerifierTest extends TestCase
{
    private string $privateKey = '';
    /** @var array<string, mixed> */
    private array $jwks;

    protected function setUp(): void
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        self::assertNotFalse($resource);
        self::assertTrue(openssl_pkey_export($resource, $this->privateKey));
        $details = openssl_pkey_get_details($resource);
        self::assertIsArray($details);
        $this->jwks = ['keys' => [[
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => 'current-key',
            'n' => $this->base64Url($details['rsa']['n']),
            'e' => $this->base64Url($details['rsa']['e']),
        ]]];
    }

    public function testVerifiesValidSignature(): void
    {
        $claims = (new JwtSignatureVerifier(['RS256']))->verify($this->token(), $this->jwks);
        self::assertSame('subject-1', $claims['sub']);
    }

    public function testRejectsTamperedPayload(): void
    {
        $segments = explode('.', $this->token());
        $segments[1] = $this->base64Url('{"sub":"attacker","exp":4102444800}');

        $this->expectException(OidcValidationException::class);
        (new JwtSignatureVerifier(['RS256']))->verify(implode('.', $segments), $this->jwks);
    }

    public function testRejectsUnknownKeyId(): void
    {
        $this->expectException(UnknownSigningKeyException::class);
        (new JwtSignatureVerifier(['RS256']))->verify($this->token('rotated-key'), $this->jwks);
    }

    public function testRejectsDisallowedAlgorithm(): void
    {
        $this->expectException(OidcValidationException::class);
        (new JwtSignatureVerifier(['ES256']))->verify($this->token(), $this->jwks);
    }

    public function testRejectsAdditionalCompactSegments(): void
    {
        $this->expectException(OidcValidationException::class);
        (new JwtSignatureVerifier(['RS256']))->verify($this->token() . '.extra', $this->jwks);
    }

    private function token(string $keyId = 'current-key'): string
    {
        return JWT::encode([
            'sub' => 'subject-1',
            'iat' => time() - 1,
            'exp' => time() + 300,
        ], $this->privateKey, 'RS256', $keyId);
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
