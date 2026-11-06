<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

interface ExistingAccountLookup
{
    public function byUserId(int $userId): ExternalIdentity;
}
