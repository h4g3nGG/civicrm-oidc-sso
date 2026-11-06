<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use InvalidArgumentException;

final class AuthorizationTransactionManager
{
    public function __construct(
        private readonly AuthorizationTransactionFactory $factory,
        private readonly AuthorizationTransactionRepository $repository,
        private readonly string $storageKey,
    ) {
        if (strlen($storageKey) < 32) {
            throw new InvalidArgumentException('OIDC transaction storage key must contain at least 32 bytes.');
        }
    }

    public function begin(string $returnPath, string $browserBinding, int $now): AuthorizationTransaction
    {
        $this->assertBrowserBinding($browserBinding);
        $transaction = $this->factory->create($returnPath, $now);
        $stored = new StoredAuthorizationTransaction(
            $transaction->nonce,
            $transaction->codeVerifier,
            $transaction->codeChallenge,
            $transaction->returnPath,
            $this->hmac('browser', $browserBinding),
            $transaction->expiresAt,
        );
        $this->repository->store($this->hmac('state', $transaction->state), $stored);
        return $transaction;
    }

    public function consume(string $state, string $browserBinding, int $now): AuthorizationTransaction
    {
        $this->assertState($state);
        $this->assertBrowserBinding($browserBinding);

        // Consumption deliberately happens before any validation. A callback
        // can therefore never be replayed, including after a failed attempt.
        $stored = $this->repository->consume($this->hmac('state', $state));
        if ($stored === null) {
            throw new OidcValidationException('OIDC login transaction is missing or was already consumed.');
        }
        if ($now >= $stored->expiresAt) {
            throw new OidcValidationException('OIDC login transaction has expired.');
        }
        $actualBinding = $this->hmac('browser', $browserBinding);
        if (!hash_equals($stored->browserBindingHash, $actualBinding)) {
            throw new OidcValidationException('OIDC login transaction does not belong to this browser session.');
        }

        return new AuthorizationTransaction(
            $state,
            $stored->nonce,
            $stored->codeVerifier,
            $stored->codeChallenge,
            $stored->returnPath,
            $stored->expiresAt,
        );
    }

    public function deleteExpired(int $now): int
    {
        return $this->repository->deleteExpired($now);
    }

    private function assertState(string $state): void
    {
        if (preg_match('/^[A-Za-z0-9_-]{43,128}$/D', $state) !== 1) {
            throw new OidcValidationException('OIDC callback state is invalid.');
        }
    }

    private function assertBrowserBinding(string $browserBinding): void
    {
        if ($browserBinding === '' || strlen($browserBinding) > 1024) {
            throw new InvalidArgumentException('OIDC browser-session binding is invalid.');
        }
    }

    private function hmac(string $purpose, string $value): string
    {
        return hash_hmac('sha256', $purpose . "\0" . $value, $this->storageKey);
    }
}
