<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

interface AuthorizationTransactionRepository
{
    /**
     * Persists a transaction under its opaque HMAC key.
     *
     * Implementations must reject duplicate keys.
     */
    public function store(string $key, StoredAuthorizationTransaction $transaction): void;

    /**
     * Atomically removes and returns a transaction.
     *
     * Concurrent calls for the same key must return the transaction at most
     * once. A failed callback must never put the transaction back.
     */
    public function consume(string $key): ?StoredAuthorizationTransaction;

    /** Removes expired records and returns the number removed. */
    public function deleteExpired(int $now): int;
}
