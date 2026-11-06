<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

interface VerifiedIdTokenDecoder
{
    /** @return array<string, mixed> */
    public function verify(string $jwt): array;
}
