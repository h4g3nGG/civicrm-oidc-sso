<?php

declare(strict_types=1);

namespace Civi\OidcSso\Migration;

final readonly class MigrationEntry
{
    /** @param list<int> $managedRoleIds */
    public function __construct(
        public string $subject,
        public int $userId,
        public array $managedRoleIds,
    ) {
    }
}
