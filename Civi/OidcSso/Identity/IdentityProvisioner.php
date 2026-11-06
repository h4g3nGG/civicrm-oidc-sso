<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

use Civi\OidcSso\Audit\AuditEvent;
use Civi\OidcSso\Audit\AuditEventWriter;

final class IdentityProvisioner
{
    public function __construct(
        private readonly ExternalIdentityRepository $identities,
        private readonly LocalAccountGateway $accounts,
        private readonly ClaimsProfileExtractor $profiles,
        private readonly ProvisioningTransaction $transaction,
        private readonly ?AuditEventWriter $audit = null,
    ) {
    }

    /** @param array<string, mixed> $claims */
    public function provision(array $claims): ExternalIdentity
    {
        $issuer = $this->requiredIdentityClaim($claims, 'iss', 1024);
        $subject = $this->requiredIdentityClaim($claims, 'sub', 255);
        $profile = $this->profiles->extract($claims);

        $created = false;
        $identity = $this->transaction->run(function () use ($issuer, $subject, $profile, &$created): ExternalIdentity {
            $existing = $this->identities->find($issuer, $subject);
            if ($existing !== null) {
                $this->accounts->update($existing, $profile);
                return $existing;
            }
            $account = $this->accounts->create($profile);
            $this->identities->create($issuer, $subject, $account);
            $created = true;
            return $account;
        });
        $this->audit?->append(
            $created ? AuditEvent::IDENTITY_CREATED : AuditEvent::IDENTITY_UPDATED,
            AuditEvent::SUCCESS,
            $identity->userId,
        );
        return $identity;
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
