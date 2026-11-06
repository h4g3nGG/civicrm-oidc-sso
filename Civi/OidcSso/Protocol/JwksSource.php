<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

interface JwksSource
{
    /**
     * @return array<string, mixed>
     */
    public function get(bool $forceRefresh = false): array;
}
