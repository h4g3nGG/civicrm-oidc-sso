<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

interface ProviderMetadataSource
{
    public function fetch(string $issuer): ProviderMetadata;
}
