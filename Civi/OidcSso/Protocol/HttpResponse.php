<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

final class HttpResponse
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $statusCode,
        public readonly array $headers,
        public readonly string $body,
    ) {
    }
}
