<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

use Civi\OidcSso\Exception\OidcValidationException;

final class ClaimsProfileExtractor
{
    /** @param array<string, mixed> $claims */
    public function extract(array $claims): UserProfile
    {
        if (($claims['email_verified'] ?? null) !== true) {
            throw new OidcValidationException('OIDC email address must be verified before provisioning.');
        }
        $email = $this->requiredString($claims, 'email');
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 254) {
            throw new OidcValidationException('OIDC email claim is invalid.');
        }
        $username = $this->requiredString($claims, 'preferred_username');
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._@+-]{0,127}$/D', $username) !== 1) {
            throw new OidcValidationException('OIDC preferred username is invalid.');
        }
        $displayName = $this->requiredString($claims, 'name');
        $givenName = $this->optionalString($claims, 'given_name');
        $familyName = $this->optionalString($claims, 'family_name');
        if ($givenName === '' && $familyName === '') {
            throw new OidcValidationException('OIDC profile must contain a given name or family name.');
        }
        return new UserProfile($email, $username, $displayName, $givenName, $familyName);
    }

    /** @param array<string, mixed> $claims */
    private function requiredString(array $claims, string $name): string
    {
        $value = $this->optionalString($claims, $name);
        if ($value === '') {
            throw new OidcValidationException("OIDC claim '{$name}' must be a non-empty string.");
        }
        return $value;
    }

    /** @param array<string, mixed> $claims */
    private function optionalString(array $claims, string $name): string
    {
        $value = $claims[$name] ?? '';
        if (!is_string($value) || strlen($value) > 255 || str_contains($value, "\0")) {
            throw new OidcValidationException("OIDC claim '{$name}' is invalid.");
        }
        return trim($value);
    }
}
