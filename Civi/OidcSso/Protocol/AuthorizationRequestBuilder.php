<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use InvalidArgumentException;

final class AuthorizationRequestBuilder
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        private readonly ProviderMetadata $provider,
        private readonly string $clientId,
        private readonly string $redirectUri,
        private readonly array $scopes = ['openid', 'profile', 'email'],
    ) {
        if ($clientId === '') {
            throw new InvalidArgumentException('OIDC client ID must not be empty.');
        }
        if (!in_array('openid', $scopes, true)) {
            throw new InvalidArgumentException("OIDC scopes must include 'openid'.");
        }
        $parts = parse_url($redirectUri);
        if ($parts === false || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])) {
            throw new InvalidArgumentException('OIDC redirect URI must be an absolute HTTPS URL.');
        }
    }

    public function build(AuthorizationTransaction $transaction): string
    {
        $query = http_build_query([
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'scope' => implode(' ', array_values(array_unique($this->scopes))),
            'state' => $transaction->state,
            'nonce' => $transaction->nonce,
            'code_challenge' => $transaction->codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);

        $separator = str_contains($this->provider->authorizationEndpoint, '?') ? '&' : '?';
        return $this->provider->authorizationEndpoint . $separator . $query;
    }
}
