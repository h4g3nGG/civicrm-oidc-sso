<?php

declare(strict_types=1);

namespace Civi\OidcSso\Web;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\AuthorizationTransactionManager;
use Civi\OidcSso\Protocol\IdTokenClaimsValidator;
use Civi\OidcSso\Protocol\TokenExchanger;
use Civi\OidcSso\Protocol\VerifiedIdTokenDecoder;

final class AuthorizationCallbackService
{
    public function __construct(
        private readonly AuthorizationTransactionManager $transactions,
        private readonly TokenExchanger $tokenExchanger,
        private readonly VerifiedIdTokenDecoder $idTokenDecoder,
        private readonly IdTokenClaimsValidator $claimsValidator,
    ) {
    }

    public function complete(
        string $state,
        ?string $authorizationCode,
        ?string $providerError,
        string $browserBinding,
        int $now,
    ): AuthenticatedIdentity {
        $transaction = $this->transactions->consume($state, $browserBinding, $now);
        if ($providerError !== null) {
            throw new OidcValidationException('The OpenID Provider rejected the login request.');
        }
        if ($authorizationCode === null || $authorizationCode === '') {
            throw new OidcValidationException('OIDC callback does not contain an authorization code.');
        }

        $tokens = $this->tokenExchanger->exchange($authorizationCode, $transaction->codeVerifier);
        $claims = $this->idTokenDecoder->verify($tokens->idToken);
        $this->claimsValidator->validate($claims, $transaction->nonce, $now);

        return new AuthenticatedIdentity($claims, $transaction->returnPath);
    }
}
