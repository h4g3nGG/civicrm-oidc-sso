<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

final class IdentityProvisioner
{
    public function __construct(
        private readonly ExternalIdentityRepository $identities,
        private readonly LocalAccountGateway $accounts,
        private readonly ClaimsProfileExtractor $profiles,
        private readonly ProvisioningTransaction $transaction,
    ) {
    }

    /** @param array<string, mixed> $claims */
    public function provision(array $claims): ExternalIdentity
    {
        $issuer = $this->requiredIdentityClaim($claims, 'iss', 1024);
        $subject = $this->requiredIdentityClaim($claims, 'sub', 255);
        $profile = $this->profiles->extract($claims);

        return $this->transaction->run(function () use ($issuer, $subject, $profile): ExternalIdentity {
            $existing = $this->identities->find($issuer, $subject);
            if ($existing !== null) {
                $this->accounts->update($existing, $profile);
                return $existing;
            }
            $created = $this->accounts->create($profile);
            $this->identities->create($issuer, $subject, $created);
            return $created;
        });
    }

    /** @param array<string, mixed> $claims */
    private function requiredIdentityClaim(array $claims, string $name, int $maximumLength): string
    {
        $value = $claims[$name] ?? null;
        if (!is_string($value) || $value === '' || strlen($value) > $maximumLength) {
            throw new \InvalidArgumentException("OIDC identity claim '{$name}' is invalid.");
        }
        return $value;
    }
}
