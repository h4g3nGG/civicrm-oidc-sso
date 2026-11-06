<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\StoredAuthorizationTransaction;
use Civi\OidcSso\Protocol\StoredAuthorizationTransactionCodec;
use PHPUnit\Framework\TestCase;

final class StoredAuthorizationTransactionCodecTest extends TestCase
{
    public function testRoundTripDoesNotExposePlaintext(): void
    {
        $codec = new StoredAuthorizationTransactionCodec(new ReversingPayloadCipher());
        $transaction = new StoredAuthorizationTransaction(
            'nonce-value',
            'code-verifier',
            'code-challenge',
            '/civicrm/home',
            str_repeat('a', 64),
            1_700_000_300,
        );

        $encoded = $codec->encode($transaction);
        self::assertStringNotContainsString('nonce-value', $encoded);
        self::assertStringNotContainsString('code-verifier', $encoded);

        $decoded = $codec->decode($encoded, $transaction->expiresAt);
        self::assertEquals($transaction, $decoded);
    }

    public function testRejectsIncompletePayload(): void
    {
        $codec = new StoredAuthorizationTransactionCodec(new ReversingPayloadCipher());

        $this->expectException(OidcValidationException::class);
        $codec->decode(strrev('{"nonce":"present"}'), 1_700_000_300);
    }
}
