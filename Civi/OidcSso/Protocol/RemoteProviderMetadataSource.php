<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;

final class RemoteProviderMetadataSource implements ProviderMetadataSource
{
    public function __construct(
        private readonly JsonDocumentFetcher $fetcher,
        private readonly DiscoveryUrlBuilder $urlBuilder,
    ) {
    }

    public function fetch(string $issuer): ProviderMetadata
    {
        $validator = new DiscoveryDocumentValidator($issuer);
        $lastException = null;
        foreach ($this->urlBuilder->candidates($issuer) as $url) {
            try {
                return $validator->validate($this->fetcher->fetch($url));
            } catch (OidcValidationException $exception) {
                $lastException = $exception;
            }
        }
        throw $lastException ?? new OidcValidationException('OpenID Connect discovery failed.');
    }
}
