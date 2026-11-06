<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

final class StoredAuthorizationTransaction
{
    public function __construct(
        public readonly string $nonce,
        public readonly string $codeVerifier,
        public readonly string $codeChallenge,
        public readonly string $returnPath,
        public readonly string $browserBindingHash,
        public readonly int $expiresAt,
    ) {
    }
}
