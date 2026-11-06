<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Exception\UnknownSigningKeyException;
use Civi\OidcSso\Protocol\IdTokenSignatureVerifier;
use Civi\OidcSso\Protocol\JwksSource;
use Civi\OidcSso\Protocol\RotatingIdTokenVerifier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RotatingIdTokenVerifierTest extends TestCase
{
    public function testRefreshesJwksExactlyOnceForUnknownKey(): void
    {
        /** @var IdTokenSignatureVerifier&MockObject $signatureVerifier */
        $signatureVerifier = $this->createMock(IdTokenSignatureVerifier::class);
        /** @var JwksSource&MockObject $source */
        $source = $this->createMock(JwksSource::class);

        $source->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(static fn(bool $refresh = false): array => ['refresh' => $refresh]);
        $signatureVerifier->expects(self::exactly(2))
            ->method('verify')
            ->willReturnCallback(static function (string $jwt, array $jwks): array {
                if ($jwks['refresh'] === false) {
                    throw new UnknownSigningKeyException('unknown');
                }
                return ['sub' => 'subject-1'];
            });

        $claims = (new RotatingIdTokenVerifier($signatureVerifier, $source))->verify('jwt');
        self::assertSame('subject-1', $claims['sub']);
    }
}
