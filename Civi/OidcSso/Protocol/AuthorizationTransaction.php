<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

final class AuthorizationTransaction
{
    public function __construct(
        public readonly string $state,
        public readonly string $nonce,
        public readonly string $codeVerifier,
        public readonly string $codeChallenge,
        public readonly string $returnPath,
        public readonly int $expiresAt,
    ) {
    }

    public function isExpired(int $now): bool
    {
        return $now >= $this->expiresAt;
    }
}
