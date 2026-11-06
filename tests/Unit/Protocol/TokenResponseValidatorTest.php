<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\HttpResponse;
use Civi\OidcSso\Protocol\TokenResponseValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TokenResponseValidatorTest extends TestCase
{
    public function testAcceptsCompleteResponse(): void
    {
        $response = $this->validate([
            'access_token' => 'access-token',
            'id_token' => 'header.payload.signature',
            'token_type' => 'Bearer',
            'expires_in' => 300,
            'refresh_token' => 'refresh-token',
            'scope' => 'openid profile',
        ]);

        self::assertSame('access-token', $response->accessToken);
        self::assertSame('header.payload.signature', $response->idToken);
        self::assertSame(300, $response->expiresIn);
        self::assertSame('refresh-token', $response->refreshToken);
    }

    /** @param array<string, mixed> $document */
    #[DataProvider('invalidResponseProvider')]
    public function testRejectsInvalidResponse(array $document): void
    {
        $this->expectException(OidcValidationException::class);
        $this->validate($document);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function invalidResponseProvider(): iterable
    {
        $valid = [
            'access_token' => 'access-token',
            'id_token' => 'header.payload.signature',
            'token_type' => 'Bearer',
        ];

        yield 'missing access token' => [[
            'id_token' => $valid['id_token'],
            'token_type' => $valid['token_type'],
        ]];
        yield 'missing ID token' => [[
            'access_token' => $valid['access_token'],
            'token_type' => $valid['token_type'],
        ]];
        yield 'wrong token type' => [array_replace($valid, ['token_type' => 'MAC'])];
        yield 'string expiry' => [array_replace($valid, ['expires_in' => '300'])];
        yield 'empty refresh token' => [array_replace($valid, ['refresh_token' => ''])];
        yield 'error in successful response' => [array_replace($valid, ['error' => 'invalid_grant'])];
    }

    public function testRejectsOAuthErrorWithoutReflectingDescription(): void
    {
        $response = new HttpResponse(400, ['Content-Type' => 'application/json'], json_encode([
            'error' => 'invalid_grant',
            'error_description' => 'secret diagnostic text',
        ], JSON_THROW_ON_ERROR));

        try {
            (new TokenResponseValidator())->validate($response);
            self::fail('Expected an OIDC validation exception.');
        } catch (OidcValidationException $exception) {
            self::assertStringContainsString('invalid_grant', $exception->getMessage());
            self::assertStringNotContainsString('secret diagnostic text', $exception->getMessage());
        }
    }

    public function testRejectsResponseWithoutJsonContentType(): void
    {
        $this->expectException(OidcValidationException::class);
        (new TokenResponseValidator())->validate(new HttpResponse(200, [], '{}'));
    }

    /** @param array<string, mixed> $document */
    private function validate(array $document): \Civi\OidcSso\Protocol\TokenResponse
    {
        return (new TokenResponseValidator())->validate(
            new HttpResponse(
                200,
                ['Content-Type' => 'application/json; charset=utf-8'],
                json_encode($document, JSON_THROW_ON_ERROR),
            ),
        );
    }
}
