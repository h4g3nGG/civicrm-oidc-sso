<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Protocol\AuthorizationTransactionFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AuthorizationTransactionFactoryTest extends TestCase
{
    public function testCreatesPkceS256Transaction(): void
    {
        $transaction = (new AuthorizationTransactionFactory())->create('/civicrm/home', 1_700_000_000);

        self::assertSame('/civicrm/home', $transaction->returnPath);
        self::assertSame(1_700_000_300, $transaction->expiresAt);
        self::assertGreaterThanOrEqual(43, strlen($transaction->state));
        self::assertGreaterThanOrEqual(43, strlen($transaction->nonce));
        self::assertSame(
            rtrim(strtr(base64_encode(hash('sha256', $transaction->codeVerifier, true)), '+/', '-_'), '='),
            $transaction->codeChallenge
        );
    }

    /** @dataProvider unsafeReturnPaths */
    public function testRejectsUnsafeReturnPath(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AuthorizationTransactionFactory())->create($path, 1_700_000_000);
    }

    /** @return iterable<string, array{string}> */
    public static function unsafeReturnPaths(): iterable
    {
        yield 'absolute URL' => ['https://attacker.example/callback'];
        yield 'scheme-relative URL' => ['//attacker.example/callback'];
        yield 'relative path' => ['civicrm/home'];
        yield 'header injection' => ["/civicrm/home\r\nLocation: https://attacker.example"];
    }
}
