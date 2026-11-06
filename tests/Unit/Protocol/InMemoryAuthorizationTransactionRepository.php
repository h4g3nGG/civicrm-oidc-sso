<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Protocol\AuthorizationTransactionRepository;
use Civi\OidcSso\Protocol\StoredAuthorizationTransaction;
use RuntimeException;

final class InMemoryAuthorizationTransactionRepository implements AuthorizationTransactionRepository
{
    /** @var array<string, StoredAuthorizationTransaction> */
    public array $records = [];

    public function store(string $key, StoredAuthorizationTransaction $transaction): void
    {
        if (isset($this->records[$key])) {
            throw new RuntimeException('Duplicate transaction key.');
        }
        $this->records[$key] = $transaction;
    }

    public function consume(string $key): ?StoredAuthorizationTransaction
    {
        $transaction = $this->records[$key] ?? null;
        unset($this->records[$key]);
        return $transaction;
    }

    public function deleteExpired(int $now): int
    {
        $deleted = 0;
        foreach ($this->records as $key => $transaction) {
            if ($now >= $transaction->expiresAt) {
                unset($this->records[$key]);
                ++$deleted;
            }
        }
        return $deleted;
    }
}
