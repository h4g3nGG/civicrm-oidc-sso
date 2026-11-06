<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

interface TokenExchanger
{
    public function exchange(string $authorizationCode, string $codeVerifier): TokenResponse;
}
