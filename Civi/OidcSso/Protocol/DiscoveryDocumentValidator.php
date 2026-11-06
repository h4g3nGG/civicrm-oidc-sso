<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;

final class DiscoveryDocumentValidator
{
    /**
     * @param list<string> $allowedSigningAlgorithms
     */
    public function __construct(
        private readonly string $configuredIssuer,
        private readonly array $allowedSigningAlgorithms = ['RS256', 'PS256', 'ES256'],
    ) {
        $this->assertHttpsUrl($configuredIssuer, 'configured issuer');
    }

    /**
     * @param array<string, mixed> $document
     */
    public function validate(array $document): ProviderMetadata
    {
        $issuer = $this->requireString($document, 'issuer');
        if (!hash_equals($this->configuredIssuer, $issuer)) {
            throw new OidcValidationException('Discovery issuer does not exactly match the configured issuer.');
        }

        $authorizationEndpoint = $this->requireHttpsUrl($document, 'authorization_endpoint');
        $tokenEndpoint = $this->requireHttpsUrl($document, 'token_endpoint');
        $jwksUri = $this->requireHttpsUrl($document, 'jwks_uri');

        $responseTypes = $this->requireStringList($document, 'response_types_supported');
        if (!in_array('code', $responseTypes, true)) {
            throw new OidcValidationException('Provider does not advertise Authorization Code Flow.');
        }

        $pkceMethods = $this->requireStringList($document, 'code_challenge_methods_supported');
        if (!in_array('S256', $pkceMethods, true)) {
            throw new OidcValidationException('Provider does not advertise PKCE S256.');
        }

        $advertisedAlgorithms = $this->requireStringList($document, 'id_token_signing_alg_values_supported');
        $acceptedAlgorithms = array_values(array_intersect($this->allowedSigningAlgorithms, $advertisedAlgorithms));
        if ($acceptedAlgorithms === []) {
            throw new OidcValidationException('Provider advertises no permitted ID-token signing algorithm.');
        }

        return new ProviderMetadata(
            $issuer,
            $authorizationEndpoint,
            $tokenEndpoint,
            $jwksUri,
            $acceptedAlgorithms,
        );
    }

    /** @param array<string, mixed> $document */
    private function requireString(array $document, string $name): string
    {
        if (!isset($document[$name]) || !is_string($document[$name]) || $document[$name] === '') {
            throw new OidcValidationException("Discovery property '{$name}' must be a non-empty string.");
        }
        return $document[$name];
    }

    /** @param array<string, mixed> $document */
    private function requireHttpsUrl(array $document, string $name): string
    {
        $value = $this->requireString($document, $name);
        $this->assertHttpsUrl($value, $name);
        return $value;
    }

    private function assertHttpsUrl(string $value, string $name): void
    {
        $parts = parse_url($value);
        if (
            $parts === false
            || ($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new OidcValidationException("OIDC {$name} must be an HTTPS URL without credentials or fragment.");
        }
    }

    /**
     * @param array<string, mixed> $document
     * @return list<string>
     */
    private function requireStringList(array $document, string $name): array
    {
        $value = $document[$name] ?? null;
        if (!is_array($value) || $value === [] || !array_is_list($value)) {
            throw new OidcValidationException("Discovery property '{$name}' must be a non-empty list.");
        }
        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw new OidcValidationException("Discovery property '{$name}' contains an invalid value.");
            }
        }
        return $value;
    }
}
