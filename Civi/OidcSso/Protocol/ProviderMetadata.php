<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

final class ProviderMetadata
{
    /** @param list<string> $idTokenSigningAlgorithms */
    public function __construct(
        public readonly string $issuer,
        public readonly string $authorizationEndpoint,
        public readonly string $tokenEndpoint,
        public readonly string $jwksUri,
        public readonly array $idTokenSigningAlgorithms,
    ) {
    }
}
