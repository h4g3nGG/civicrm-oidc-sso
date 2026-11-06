<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Web;

use Civi\OidcSso\Protocol\AuthorizationTransactionFactory;
use Civi\OidcSso\Protocol\AuthorizationTransactionManager;
use Civi\OidcSso\Protocol\ProviderMetadata;
use Civi\OidcSso\Protocol\ProviderMetadataSource;
use Civi\OidcSso\Tests\Unit\Protocol\InMemoryAuthorizationTransactionRepository;
use Civi\OidcSso\Web\LoginStartService;
use PHPUnit\Framework\TestCase;

final class LoginStartServiceTest extends TestCase
{
    public function testCreatesTransactionAndAuthorizationRedirect(): void
    {
        $source = $this->createMock(ProviderMetadataSource::class);
        $source->expects(self::once())->method('fetch')->with('https://id.example.org/application/o/crm')
            ->willReturn(new ProviderMetadata(
                'https://id.example.org/application/o/crm',
                'https://id.example.org/application/o/authorize/',
                'https://id.example.org/application/o/token/',
                'https://id.example.org/application/o/crm/jwks/',
                ['RS256'],
            ));
        $repository = new InMemoryAuthorizationTransactionRepository();
        $service = new LoginStartService(
            $source,
            new AuthorizationTransactionManager(
                new AuthorizationTransactionFactory(),
                $repository,
                str_repeat('k', 32),
            ),
            'https://id.example.org/application/o/crm',
            'client-id',
            'https://crm.example.org/civicrm/oidc/callback',
            ['openid', 'profile', 'email', 'groups'],
        );

        $url = $service->start('/civicrm/home', 'browser-binding', 1700000000);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        self::assertStringStartsWith('https://id.example.org/application/o/authorize/?', $url);
        self::assertSame('code', $query['response_type']);
        self::assertSame('client-id', $query['client_id']);
        self::assertSame('openid profile email groups', $query['scope']);
        self::assertSame('S256', $query['code_challenge_method']);
        self::assertCount(1, $repository->records);
    }
}
