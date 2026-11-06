<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Protocol\HttpRequest;
use Civi\OidcSso\Protocol\HttpResponse;
use Civi\OidcSso\Protocol\HttpTransport;
use Civi\OidcSso\Protocol\ProviderMetadata;
use Civi\OidcSso\Protocol\TokenEndpointClient;
use Civi\OidcSso\Protocol\TokenResponseValidator;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class TokenEndpointClientTest extends TestCase
{
    private const VERIFIER = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-._~';

    public function testPublicClientSendsPkceForm(): void
    {
        $request = $this->exchange(TokenEndpointClient::AUTH_NONE);
        parse_str($request->body, $form);

        self::assertSame('POST', $request->method);
        self::assertSame('https://id.example.org/token', $request->url);
        self::assertSame('authorization_code', $form['grant_type']);
        self::assertSame('auth-code', $form['code']);
        self::assertSame('client-id', $form['client_id']);
        self::assertSame(self::VERIFIER, $form['code_verifier']);
        self::assertArrayNotHasKey('client_secret', $form);
        self::assertArrayNotHasKey('Authorization', $request->headers);
    }

    public function testBasicAuthenticationKeepsSecretOutOfBody(): void
    {
        $request = $this->exchange(TokenEndpointClient::AUTH_CLIENT_SECRET_BASIC, 's:ecret');
        parse_str($request->body, $form);

        self::assertArrayNotHasKey('client_id', $form);
        self::assertArrayNotHasKey('client_secret', $form);
        self::assertSame(
            'Basic ' . base64_encode('client-id:s%3Aecret'),
            $request->headers['Authorization'],
        );
    }

    public function testPostAuthenticationUsesFormCredentials(): void
    {
        $request = $this->exchange(TokenEndpointClient::AUTH_CLIENT_SECRET_POST, 'secret');
        parse_str($request->body, $form);

        self::assertSame('client-id', $form['client_id']);
        self::assertSame('secret', $form['client_secret']);
        self::assertArrayNotHasKey('Authorization', $request->headers);
    }

    public function testRejectsInvalidPkceVerifierBeforeNetworkRequest(): void
    {
        /** @var HttpTransport&MockObject $transport */
        $transport = $this->createMock(HttpTransport::class);
        $transport->expects(self::never())->method('send');

        $this->expectException(InvalidArgumentException::class);
        $this->client($transport)->exchange('auth-code', 'too-short');
    }

    public function testRejectsRedirectUriFragment(): void
    {
        /** @var HttpTransport&MockObject $transport */
        $transport = $this->createMock(HttpTransport::class);

        $this->expectException(InvalidArgumentException::class);
        new TokenEndpointClient(
            $this->provider(),
            $transport,
            new TokenResponseValidator(),
            'client-id',
            'https://crm.example.org/oidc/callback#fragment',
        );
    }

    private function exchange(string $authenticationMethod, ?string $secret = null): HttpRequest
    {
        $request = null;
        /** @var HttpTransport&MockObject $transport */
        $transport = $this->createMock(HttpTransport::class);
        $transport->expects(self::once())
            ->method('send')
            ->willReturnCallback(static function (HttpRequest $sent) use (&$request): HttpResponse {
                $request = $sent;
                return new HttpResponse(200, ['Content-Type' => 'application/json'], json_encode([
                    'access_token' => 'access-token',
                    'id_token' => 'header.payload.signature',
                    'token_type' => 'Bearer',
                ], JSON_THROW_ON_ERROR));
            });

        $this->client($transport, $authenticationMethod, $secret)->exchange('auth-code', self::VERIFIER);
        self::assertInstanceOf(HttpRequest::class, $request);
        return $request;
    }

    private function client(
        HttpTransport $transport,
        string $authenticationMethod = TokenEndpointClient::AUTH_NONE,
        ?string $secret = null,
    ): TokenEndpointClient {
        return new TokenEndpointClient(
            $this->provider(),
            $transport,
            new TokenResponseValidator(),
            'client-id',
            'https://crm.example.org/oidc/callback',
            $authenticationMethod,
            $secret,
        );
    }

    private function provider(): ProviderMetadata
    {
        return new ProviderMetadata(
            'https://id.example.org/',
            'https://id.example.org/authorize',
            'https://id.example.org/token',
            'https://id.example.org/jwks',
            ['RS256'],
        );
    }
}
