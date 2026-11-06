<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use JsonException;
use UnexpectedValueException;

final class RoleMappingSetting
{
    /** @return array<string, list<int>> */
    public static function decode(mixed $value): array
    {
        if (is_string($value)) {
            try {
                $value = json_decode($value, true, 16, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new UnexpectedValueException('OIDC role mapping is not valid JSON.', 0, $exception);
            }
        }

        if (!is_array($value) || array_is_list($value)) {
            throw new UnexpectedValueException('OIDC role mapping must be an object-like array.');
        }

        foreach ($value as $group => $roleIds) {
            if (!is_string($group) || $group === '' || !is_array($roleIds) || !array_is_list($roleIds)) {
                throw new UnexpectedValueException('OIDC role mapping has an invalid group entry.');
            }
            foreach ($roleIds as $roleId) {
                if (!is_int($roleId) || $roleId < 1) {
                    throw new UnexpectedValueException('OIDC role mapping contains an invalid role ID.');
                }
            }
        }

        /** @var array<string, list<int>> $value */
        return $value;
    }
}
