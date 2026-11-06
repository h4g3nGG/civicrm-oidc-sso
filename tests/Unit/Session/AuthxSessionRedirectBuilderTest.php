<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Session;

use Civi\OidcSso\Session\AuthxSessionRedirectBuilder;
use Civi\OidcSso\Session\JwtEncoder;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuthxSessionRedirectBuilderTest extends TestCase
{
    public function testBuildsShortLivedAuthxAutoSessionRedirect(): void
    {
        $encoder = $this->createMock(JwtEncoder::class);
        $encoder->expects(self::once())->method('encode')->with(self::callback(
            static fn(array $claims): bool => $claims['sub'] === 'cid:42'
                && $claims['scope'] === 'authx'
                && $claims['iat'] === 1700000000
                && $claims['exp'] === 1700000060
                && is_string($claims['jti']),
        ))->willReturn('signed.jwt');

        $url = (new AuthxSessionRedirectBuilder($encoder))->build(42, '/civicrm/home?reset=1', 1700000000);
        self::assertSame(
            '/civicrm/home?reset=1&_authx=Bearer%20signed.jwt&_authxSes=1',
            $url,
        );
    }

    public function testRejectsExternalReturnUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AuthxSessionRedirectBuilder($this->createStub(JwtEncoder::class)))
            ->build(42, 'https://attacker.example.org/', 1700000000);
    }
}
