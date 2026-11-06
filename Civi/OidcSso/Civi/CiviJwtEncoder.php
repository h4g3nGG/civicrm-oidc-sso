<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Session\JwtEncoder;
use RuntimeException;

final class CiviJwtEncoder implements JwtEncoder
{
    public function encode(array $claims): string
    {
        $token = \Civi::service('crypto.jwt')->encode($claims);
        if (!is_string($token) || $token === '') {
            throw new RuntimeException('CiviCRM AuthX JWT service returned an invalid token.');
        }
        return $token;
    }
}
