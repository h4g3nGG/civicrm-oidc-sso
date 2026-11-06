<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

final class UserProfile
{
    public function __construct(
        public readonly string $email,
        public readonly string $username,
        public readonly string $displayName,
        public readonly string $givenName,
        public readonly string $familyName,
    ) {
    }
}
