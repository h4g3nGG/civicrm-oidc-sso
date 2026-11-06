<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Protocol\DiscoveryUrlBuilder;
use Civi\OidcSso\Protocol\HttpRequest;
use Civi\OidcSso\Protocol\HttpResponse;
use Civi\OidcSso\Protocol\HttpTransport;
use Civi\OidcSso\Protocol\JsonDocumentFetcher;
use Civi\OidcSso\Protocol\RemoteProviderMetadataSource;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RemoteProviderMetadataSourceTest extends TestCase
{
    public function testFetchesAndValidatesProviderMetadata(): void
    {
        $issuer = 'https://id.example.org/application/o/civicrm';
        $document = json_encode([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer . '/authorize/',
            'token_endpoint' => $issuer . '/token/',
            'jwks_uri' => $issuer . '/jwks/',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'code_challenge_methods_supported' => ['S256'],
        ], JSON_THROW_ON_ERROR);

        /** @var HttpTransport&MockObject $transport */
        $transport = $this->createMock(HttpTransport::class);
        $transport->expects(self::once())
            ->method('send')
            ->with(self::callback(static fn(HttpRequest $request): bool => $request->url
                === 'https://id.example.org/.well-known/openid-configuration/application/o/civicrm'))
            ->willReturn(new HttpResponse(200, ['Content-Type' => 'application/json'], $document));

        $source = new RemoteProviderMetadataSource(
            new JsonDocumentFetcher($transport),
            new DiscoveryUrlBuilder(),
        );
        $metadata = $source->fetch($issuer);

        self::assertSame($issuer, $metadata->issuer);
        self::assertSame($issuer . '/jwks/', $metadata->jwksUri);
    }

    public function testFallsBackToProviderSuffixDiscoveryAndStillValidatesIssuer(): void
    {
        $issuer = 'https://id.example.org/application/o/civicrm';
        $document = json_encode([
            'issuer' => $issuer,
            'authorization_endpoint' => $issuer . '/authorize/',
            'token_endpoint' => $issuer . '/token/',
            'jwks_uri' => $issuer . '/jwks/',
            'response_types_supported' => ['code'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => ['RS256'],
            'code_challenge_methods_supported' => ['S256'],
        ], JSON_THROW_ON_ERROR);

        /** @var HttpTransport&MockObject $transport */
        $transport = $this->createMock(HttpTransport::class);
        $transport->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(static function (HttpRequest $request) use ($document): HttpResponse {
                if ($request->url === 'https://id.example.org/.well-known/openid-configuration/application/o/civicrm') {
                    return new HttpResponse(404, ['Content-Type' => 'text/html'], 'Not found');
                }
                self::assertSame(
                    'https://id.example.org/application/o/civicrm/.well-known/openid-configuration',
                    $request->url,
                );
                return new HttpResponse(200, ['Content-Type' => 'application/json'], $document);
            });

        $source = new RemoteProviderMetadataSource(
            new JsonDocumentFetcher($transport),
            new DiscoveryUrlBuilder(),
        );
        self::assertSame($issuer, $source->fetch($issuer)->issuer);
    }
}
