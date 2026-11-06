<?php

declare(strict_types=1);

namespace Civi\OidcSso\Session;

interface JwtEncoder
{
    /** @param array<string, mixed> $claims */
    public function encode(array $claims): string;
}
