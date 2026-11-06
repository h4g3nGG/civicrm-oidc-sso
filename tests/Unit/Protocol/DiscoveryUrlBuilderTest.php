<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Protocol\DiscoveryUrlBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DiscoveryUrlBuilderTest extends TestCase
{
    public function testBuildsRootIssuerUrl(): void
    {
        self::assertSame(
            'https://id.example.org/.well-known/openid-configuration',
            (new DiscoveryUrlBuilder())->build('https://id.example.org/'),
        );
    }

    public function testBuildsPathIssuerUrlAccordingToDiscoverySpec(): void
    {
        self::assertSame(
            'https://id.example.org/.well-known/openid-configuration/application/o/civicrm',
            (new DiscoveryUrlBuilder())->build('https://id.example.org/application/o/civicrm/'),
        );
    }

    public function testOffersProviderSuffixFallbackForPathIssuer(): void
    {
        self::assertSame([
            'https://id.example.org/.well-known/openid-configuration/application/o/civicrm',
            'https://id.example.org/application/o/civicrm/.well-known/openid-configuration',
        ], (new DiscoveryUrlBuilder())->candidates('https://id.example.org/application/o/civicrm/'));
    }

    public function testRejectsIssuerQuery(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new DiscoveryUrlBuilder())->build('https://id.example.org/?tenant=1');
    }
}
