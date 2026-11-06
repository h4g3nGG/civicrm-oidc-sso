<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;

/**
 * Validates registered ID-token claims after cryptographic JWT verification.
 */
final class IdTokenClaimsValidator
{
    public function __construct(
        private readonly string $issuer,
        private readonly string $clientId,
        private readonly int $clockSkewSeconds = 60,
        private readonly int $maximumTokenAgeSeconds = 900,
    ) {
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function validate(array $claims, string $expectedNonce, int $now): void
    {
        $this->requireString($claims, 'sub');
        $issuer = $this->requireString($claims, 'iss');
        $nonce = $this->requireString($claims, 'nonce');
        $expiry = $this->requireInteger($claims, 'exp');
        $issuedAt = $this->requireInteger($claims, 'iat');

        if (!hash_equals($this->issuer, $issuer)) {
            throw new OidcValidationException('ID token issuer does not match the configured issuer.');
        }
        if (!hash_equals($expectedNonce, $nonce)) {
            throw new OidcValidationException('ID token nonce does not match the login transaction.');
        }
        if ($expiry <= $now - $this->clockSkewSeconds) {
            throw new OidcValidationException('ID token has expired.');
        }
        if ($issuedAt > $now + $this->clockSkewSeconds) {
            throw new OidcValidationException('ID token was issued in the future.');
        }
        if ($issuedAt < $now - $this->maximumTokenAgeSeconds - $this->clockSkewSeconds) {
            throw new OidcValidationException('ID token is older than the accepted login window.');
        }
        if (isset($claims['nbf'])) {
            $notBefore = $this->requireInteger($claims, 'nbf');
            if ($notBefore > $now + $this->clockSkewSeconds) {
                throw new OidcValidationException('ID token is not valid yet.');
            }
        }

        $audiences = $this->audiences($claims['aud'] ?? null);
        if (!in_array($this->clientId, $audiences, true)) {
            throw new OidcValidationException('ID token audience does not contain this client.');
        }
        if (count($audiences) > 1) {
            $authorizedParty = $this->requireString($claims, 'azp');
            if (!hash_equals($this->clientId, $authorizedParty)) {
                throw new OidcValidationException('ID token authorized party does not match this client.');
            }
        } elseif (isset($claims['azp']) && !hash_equals($this->clientId, $this->requireString($claims, 'azp'))) {
            throw new OidcValidationException('ID token authorized party does not match this client.');
        }
    }

    /** @param array<string, mixed> $claims */
    private function requireString(array $claims, string $name): string
    {
        if (!isset($claims[$name]) || !is_string($claims[$name]) || $claims[$name] === '') {
            throw new OidcValidationException("ID token claim '{$name}' must be a non-empty string.");
        }
        return $claims[$name];
    }

    /** @param array<string, mixed> $claims */
    private function requireInteger(array $claims, string $name): int
    {
        if (!isset($claims[$name]) || !is_int($claims[$name])) {
            throw new OidcValidationException("ID token claim '{$name}' must be an integer.");
        }
        return $claims[$name];
    }

    /** @return list<string> */
    private function audiences(mixed $audience): array
    {
        if (is_string($audience) && $audience !== '') {
            return [$audience];
        }
        if (is_array($audience) && $audience !== [] && array_is_list($audience)) {
            foreach ($audience as $value) {
                if (!is_string($value) || $value === '') {
                    throw new OidcValidationException('ID token audience contains an invalid value.');
                }
            }
            return $audience;
        }
        throw new OidcValidationException('ID token audience must be a string or a non-empty string list.');
    }
}
