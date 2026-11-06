<?php

declare(strict_types=1);

namespace Civi\OidcSso\Audit;

interface AuditEventWriter
{
    /** @param array<string, bool|int|string> $details */
    public function append(string $eventType, string $outcome, ?int $userId = null, array $details = []): void;
}
