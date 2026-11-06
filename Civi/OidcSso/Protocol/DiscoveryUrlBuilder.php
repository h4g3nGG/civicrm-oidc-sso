<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use InvalidArgumentException;

final class DiscoveryUrlBuilder
{
    public function build(string $issuer): string
    {
        return $this->candidates($issuer)[0];
    }

    /** @return non-empty-list<string> */
    public function candidates(string $issuer): array
    {
        $parts = parse_url($issuer);
        if (
            $parts === false
            || ($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException('OIDC issuer must be an absolute HTTPS URL.');
        }
        $authority = 'https://' . $parts['host'];
        if (isset($parts['port'])) {
            $authority .= ':' . $parts['port'];
        }
        $issuerPath = rtrim($parts['path'] ?? '', '/');
        $standard = $authority . '/.well-known/openid-configuration' . $issuerPath;
        if ($issuerPath === '') {
            return [$standard];
        }
        return [$standard, $authority . $issuerPath . '/.well-known/openid-configuration'];
    }
}
