<?php

declare(strict_types=1);

namespace Civi\OidcSso\Migration;

final class MigrationEntry
{
    /** @param list<int> $managedRoleIds */
    public function __construct(
        public readonly string $subject,
        public readonly int $userId,
        public readonly array $managedRoleIds,
    ) {
    }
}
