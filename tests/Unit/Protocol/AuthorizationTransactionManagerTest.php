<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\AuthorizationTransactionFactory;
use Civi\OidcSso\Protocol\AuthorizationTransactionManager;
use Civi\OidcSso\Protocol\AuthorizationTransactionRepository;
use PHPUnit\Framework\TestCase;

final class AuthorizationTransactionManagerTest extends TestCase
{
    private const STORAGE_KEY = 'test-storage-key-with-at-least-32-bytes';

    public function testConsumesTransactionExactlyOnce(): void
    {
        $manager = $this->manager();
        $created = $manager->begin('/civicrm/home', 'browser-session-1', 1_700_000_000);
        $consumed = $manager->consume($created->state, 'browser-session-1', 1_700_000_001);

        self::assertSame($created->nonce, $consumed->nonce);
        self::assertSame($created->codeVerifier, $consumed->codeVerifier);

        $this->expectException(OidcValidationException::class);
        $manager->consume($created->state, 'browser-session-1', 1_700_000_002);
    }

    public function testPersistenceKeyDoesNotContainRawState(): void
    {
        $repository = new InMemoryAuthorizationTransactionRepository();
        $manager = $this->manager($repository);
        $created = $manager->begin('/civicrm/home', 'browser-session-1', 1_700_000_000);

        self::assertCount(1, $repository->records);
        self::assertArrayNotHasKey($created->state, $repository->records);
        self::assertStringNotContainsString($created->state, (string) array_key_first($repository->records));
    }

    public function testWrongBrowserBindingBurnsTransaction(): void
    {
        $manager = $this->manager();
        $created = $manager->begin('/civicrm/home', 'browser-session-1', 1_700_000_000);

        try {
            $manager->consume($created->state, 'browser-session-2', 1_700_000_001);
            self::fail('Expected the browser binding to be rejected.');
        } catch (OidcValidationException) {
            $this->expectException(OidcValidationException::class);
            $manager->consume($created->state, 'browser-session-1', 1_700_000_002);
        }
    }

    public function testExpiredTransactionIsConsumedAndRejected(): void
    {
        $manager = $this->manager();
        $created = $manager->begin('/civicrm/home', 'browser-session-1', 1_700_000_000);

        $this->expectException(OidcValidationException::class);
        $manager->consume($created->state, 'browser-session-1', 1_700_000_300);
    }

    public function testParallelTransactionsRemainIndependent(): void
    {
        $manager = $this->manager();
        $first = $manager->begin('/first', 'browser-session-1', 1_700_000_000);
        $second = $manager->begin('/second', 'browser-session-1', 1_700_000_000);

        self::assertSame('/second', $manager->consume(
            $second->state,
            'browser-session-1',
            1_700_000_001,
        )->returnPath);
        self::assertSame('/first', $manager->consume(
            $first->state,
            'browser-session-1',
            1_700_000_001,
        )->returnPath);
    }

    public function testDeletesExpiredTransactions(): void
    {
        $repository = new InMemoryAuthorizationTransactionRepository();
        $manager = $this->manager($repository);
        $manager->begin('/expired', 'browser-session-1', 1_700_000_000);
        $manager->begin('/active', 'browser-session-1', 1_700_000_200);

        self::assertSame(1, $manager->deleteExpired(1_700_000_300));
        self::assertCount(1, $repository->records);
    }

    private function manager(
        ?AuthorizationTransactionRepository $repository = null,
    ): AuthorizationTransactionManager {
        return new AuthorizationTransactionManager(
            new AuthorizationTransactionFactory(),
            $repository ?? new InMemoryAuthorizationTransactionRepository(),
            self::STORAGE_KEY,
        );
    }
}
