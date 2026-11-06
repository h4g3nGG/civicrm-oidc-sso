<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use InvalidArgumentException;

final class DiscoveryUrlBuilder
{
    public function build(string $issuer): string
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
        return $authority . '/.well-known/openid-configuration' . $issuerPath;
    }
}
