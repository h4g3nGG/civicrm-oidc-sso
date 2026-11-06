<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

final class RemoteProviderMetadataSource implements ProviderMetadataSource
{
    public function __construct(
        private readonly JsonDocumentFetcher $fetcher,
        private readonly DiscoveryUrlBuilder $urlBuilder,
    ) {
    }

    public function fetch(string $issuer): ProviderMetadata
    {
        return (new DiscoveryDocumentValidator($issuer))->validate(
            $this->fetcher->fetch($this->urlBuilder->build($issuer)),
        );
    }
}
