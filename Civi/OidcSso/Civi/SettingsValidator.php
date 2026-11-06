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

    public static function roleMapping(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        try {
            $decoded = json_decode($value, false, 16, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }
        if (!$decoded instanceof \stdClass) {
            return false;
        }
        $mapping = get_object_vars($decoded);
        foreach ($mapping as $group => $roles) {
            if (!is_string($group) || $group === '' || strlen($group) > 255 || !is_array($roles)) {
                return false;
            }
            foreach ($roles as $roleId) {
                if (!is_int($roleId) || $roleId < 1) {
                    return false;
                }
            }
        }
        return true;
    }
}
