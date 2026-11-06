<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Protocol\AuthorizationRequestBuilder;
use Civi\OidcSso\Protocol\AuthorizationTransaction;
use Civi\OidcSso\Protocol\ProviderMetadata;
use PHPUnit\Framework\TestCase;

final class AuthorizationRequestBuilderTest extends TestCase
{
    public function testBuildsAuthorizationCodeRequestWithPkceAndNonce(): void
    {
        $provider = new ProviderMetadata(
            'https://id.example.org/',
            'https://id.example.org/authorize',
            'https://id.example.org/token',
            'https://id.example.org/jwks',
            ['RS256'],
        );
        $transaction = new AuthorizationTransaction('state', 'nonce', 'verifier', 'challenge', '/', 123);
        $url = (new AuthorizationRequestBuilder(
            $provider,
            'client-id',
            'https://crm.example.org/civicrm/oidc/callback',
            ['openid', 'profile', 'groups'],
        ))->build($transaction);

        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        self::assertSame('code', $query['response_type']);
        self::assertSame('client-id', $query['client_id']);
        self::assertSame('openid profile groups', $query['scope']);
        self::assertSame('state', $query['state']);
        self::assertSame('nonce', $query['nonce']);
        self::assertSame('challenge', $query['code_challenge']);
        self::assertSame('S256', $query['code_challenge_method']);
    }
}
