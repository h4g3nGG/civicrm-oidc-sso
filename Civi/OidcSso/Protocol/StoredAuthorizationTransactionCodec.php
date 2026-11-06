<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Throwable;

final class StoredAuthorizationTransactionCodec
{
    public function __construct(private readonly PayloadCipher $cipher)
    {
    }

    public function encode(StoredAuthorizationTransaction $transaction): string
    {
        $json = json_encode([
            'nonce' => $transaction->nonce,
            'code_verifier' => $transaction->codeVerifier,
            'code_challenge' => $transaction->codeChallenge,
            'return_path' => $transaction->returnPath,
            'browser_binding_hash' => $transaction->browserBindingHash,
        ], JSON_THROW_ON_ERROR);
        return $this->cipher->encrypt($json);
    }

    public function decode(string $payload, int $expiresAt): StoredAuthorizationTransaction
    {
        try {
            $json = $this->cipher->decrypt($payload);
            $data = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new OidcValidationException('Stored OIDC login transaction cannot be decrypted.', 0, $exception);
        }
        if (!is_array($data) || array_is_list($data)) {
            throw new OidcValidationException('Stored OIDC login transaction is invalid.');
        }

        return new StoredAuthorizationTransaction(
            $this->requiredString($data, 'nonce'),
            $this->requiredString($data, 'code_verifier'),
            $this->requiredString($data, 'code_challenge'),
            $this->requiredString($data, 'return_path'),
            $this->requiredString($data, 'browser_binding_hash'),
            $expiresAt,
        );
    }

    /** @param array<string, mixed> $data */
    private function requiredString(array $data, string $name): string
    {
        $value = $data[$name] ?? null;
        if (!is_string($value) || $value === '') {
            throw new OidcValidationException("Stored OIDC field '{$name}' is invalid.");
        }
        return $value;
    }
}
