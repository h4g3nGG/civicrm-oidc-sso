<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

interface IdTokenSignatureVerifier
{
    /**
     * @param array<string, mixed> $jwks
     * @return array<string, mixed>
     */
    public function verify(string $jwt, array $jwks): array;
}
