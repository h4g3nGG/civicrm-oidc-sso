<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

use Civi\OidcSso\Exception\OidcValidationException;

final class ExistingIdentityLinker
{
    public function __construct(
        private readonly ExternalIdentityRepository $identities,
        private readonly ExistingAccountLookup $accounts,
        private readonly ProvisioningTransaction $transaction,
    ) {
    }

    public function link(string $issuer, string $subject, int $userId): ExternalIdentity
    {
        if ($issuer === '' || $subject === '' || $userId < 1) {
            throw new \InvalidArgumentException('Existing identity link input is invalid.');
        }
        return $this->transaction->run(function () use ($issuer, $subject, $userId): ExternalIdentity {
            if ($this->identities->find($issuer, $subject) !== null) {
                throw new OidcValidationException('OIDC identity is already linked.');
            }
            $identity = $this->accounts->byUserId($userId);
            $this->identities->create($issuer, $subject, $identity);
            return $identity;
        });
    }
}
