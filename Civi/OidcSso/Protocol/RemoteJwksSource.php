<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

final class RemoteJwksSource implements JwksSource
{
    /** @var array<string, mixed>|null */
    private ?array $cached = null;

    public function __construct(
        private readonly JsonDocumentFetcher $fetcher,
        private readonly string $jwksUri,
    ) {
    }

    public function get(bool $forceRefresh = false): array
    {
        if ($this->cached === null || $forceRefresh) {
            $this->cached = $this->fetcher->fetch($this->jwksUri);
        }
        return $this->cached;
    }
}
