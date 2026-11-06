<?php

declare(strict_types=1);

namespace Civi\OidcSso\Audit;

use InvalidArgumentException;

final class AuditDetails
{
    /** @param array<string, bool|int|string> $details */
    public static function encode(array $details): string
    {
        $allowed = ['added_count', 'entry_count', 'reason_code', 'removed_count'];
        foreach ($details as $key => $value) {
            if (!in_array($key, $allowed, true)) {
                throw new InvalidArgumentException("Audit detail '{$key}' is not allow-listed.");
            }
            if (is_string($value) && preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $value) !== 1) {
                throw new InvalidArgumentException("Audit detail '{$key}' is not a controlled code.");
            }
        }
        ksort($details);
        $json = json_encode($details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if (strlen($json) > 1024) {
            throw new InvalidArgumentException('Audit details exceed the size limit.');
        }
        return $json;
    }
}
