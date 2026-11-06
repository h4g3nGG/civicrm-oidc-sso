<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

final class ExternalIdentity
{
    public function __construct(
        public readonly int $contactId,
        public readonly int $userId,
        public readonly int $ufMatchId,
    ) {
    }
}
