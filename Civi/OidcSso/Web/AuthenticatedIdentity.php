<?php

declare(strict_types=1);

namespace Civi\OidcSso\Web;

final class AuthenticatedIdentity
{
    /** @param array<string, mixed> $claims */
    public function __construct(
        public readonly array $claims,
        public readonly string $returnPath,
    ) {
    }
}
