<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use InvalidArgumentException;

final class AuthorizationTransactionFactory
{
    public function __construct(private readonly int $lifetimeSeconds = 300)
    {
        if ($lifetimeSeconds < 60 || $lifetimeSeconds > 900) {
            throw new InvalidArgumentException('OIDC transaction lifetime must be between 60 and 900 seconds.');
        }
    }

    public function create(string $returnPath, int $now): AuthorizationTransaction
    {
        if (!$this->isSafeLocalPath($returnPath)) {
            throw new InvalidArgumentException('Return path must be a local absolute path.');
        }

        $verifier = $this->base64Url(random_bytes(64));

        return new AuthorizationTransaction(
            $this->base64Url(random_bytes(32)),
            $this->base64Url(random_bytes(32)),
            $verifier,
            $this->base64Url(hash('sha256', $verifier, true)),
            $returnPath,
            $now + $this->lifetimeSeconds,
        );
    }

    private function isSafeLocalPath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, "\r")
            && !str_contains($path, "\n")
            && filter_var($path, FILTER_VALIDATE_URL) === false;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
