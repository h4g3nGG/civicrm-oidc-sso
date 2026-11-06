<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

final class SettingsValidator
{
    public static function issuer(mixed $value): bool
    {
        if ($value === '') {
            return true;
        }
        if (!is_string($value)) {
            return false;
        }
        $parts = parse_url($value);
        return $parts !== false
            && ($parts['scheme'] ?? '') === 'https'
            && !empty($parts['host'])
            && !isset($parts['user'])
            && !isset($parts['pass'])
            && !isset($parts['query'])
            && !isset($parts['fragment']);
    }

    public static function scopes(mixed $value): bool
    {
        if (!is_string($value) || preg_match('/^[A-Za-z0-9._:-]+(?: [A-Za-z0-9._:-]+)*$/D', $value) !== 1) {
            return false;
        }
        return in_array('openid', explode(' ', $value), true);
    }

    public static function groupClaim(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[A-Za-z_][A-Za-z0-9_.:-]{0,127}$/D', $value) === 1;
    }
}
