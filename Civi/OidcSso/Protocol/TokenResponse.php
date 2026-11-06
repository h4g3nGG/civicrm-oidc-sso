<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

final class TokenResponse
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $idToken,
        public readonly string $tokenType,
        public readonly ?int $expiresIn,
        public readonly ?string $refreshToken,
        public readonly ?string $scope,
    ) {
    }
}
