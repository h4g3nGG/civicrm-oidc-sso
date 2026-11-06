<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use InvalidArgumentException;

final class TokenEndpointClient implements TokenExchanger
{
    public const AUTH_NONE = 'none';
    public const AUTH_CLIENT_SECRET_BASIC = 'client_secret_basic';
    public const AUTH_CLIENT_SECRET_POST = 'client_secret_post';

    public function __construct(
        private readonly ProviderMetadata $provider,
        private readonly HttpTransport $transport,
        private readonly TokenResponseValidator $responseValidator,
        private readonly string $clientId,
        private readonly string $redirectUri,
        private readonly string $authenticationMethod = self::AUTH_NONE,
        private readonly ?string $clientSecret = null,
    ) {
        if ($clientId === '') {
            throw new InvalidArgumentException('OIDC client ID must not be empty.');
        }
        if (!$this->isAbsoluteHttpsUrl($redirectUri)) {
            throw new InvalidArgumentException('OIDC redirect URI must be an absolute HTTPS URL.');
        }
        $authenticationMethodIsSupported = in_array(
            $authenticationMethod,
            [self::AUTH_NONE, self::AUTH_CLIENT_SECRET_BASIC, self::AUTH_CLIENT_SECRET_POST],
            true,
        );
        if (!$authenticationMethodIsSupported) {
            throw new InvalidArgumentException('Unsupported token endpoint authentication method.');
        }
        if ($authenticationMethod !== self::AUTH_NONE && ($clientSecret === null || $clientSecret === '')) {
            throw new InvalidArgumentException('Selected token endpoint authentication requires a client secret.');
        }
        if ($authenticationMethod === self::AUTH_NONE && $clientSecret !== null) {
            throw new InvalidArgumentException('Public OIDC clients must not configure a client secret.');
        }
    }

    public function exchange(string $authorizationCode, string $codeVerifier): TokenResponse
    {
        if ($authorizationCode === '') {
            throw new InvalidArgumentException('OIDC authorization code must not be empty.');
        }
        if (preg_match('/^[A-Za-z0-9._~-]{43,128}$/D', $codeVerifier) !== 1) {
            throw new InvalidArgumentException('OIDC PKCE verifier is invalid.');
        }

        $parameters = [
            'grant_type' => 'authorization_code',
            'code' => $authorizationCode,
            'redirect_uri' => $this->redirectUri,
        ];
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        if ($this->authenticationMethod === self::AUTH_CLIENT_SECRET_BASIC) {
            $credential = rawurlencode($this->clientId) . ':' . rawurlencode((string) $this->clientSecret);
            $headers['Authorization'] = 'Basic ' . base64_encode($credential);
        } else {
            $parameters['client_id'] = $this->clientId;
            if ($this->authenticationMethod === self::AUTH_CLIENT_SECRET_POST) {
                $parameters['client_secret'] = (string) $this->clientSecret;
            }
        }
        $parameters['code_verifier'] = $codeVerifier;

        $request = new HttpRequest(
            'POST',
            $this->provider->tokenEndpoint,
            $headers,
            http_build_query($parameters, '', '&', PHP_QUERY_RFC3986),
        );

        return $this->responseValidator->validate($this->transport->send($request));
    }

    private function isAbsoluteHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);
        return $parts !== false
            && ($parts['scheme'] ?? '') === 'https'
            && !empty($parts['host'])
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && !isset($parts['fragment']);
    }
}
