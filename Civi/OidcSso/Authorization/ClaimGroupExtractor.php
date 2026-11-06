<?php

declare(strict_types=1);

namespace Civi\OidcSso\Authorization;

use Civi\OidcSso\Exception\OidcValidationException;

final class ClaimGroupExtractor
{
    public function __construct(private readonly string $claimPath)
    {
    }

    /** @param array<string, mixed> $claims @return list<string> */
    public function extract(array $claims): array
    {
        $value = $claims;
        foreach (explode('.', $this->claimPath) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return [];
            }
            $value = $value[$segment];
        }
        if (!is_array($value) || !array_is_list($value)) {
            throw new OidcValidationException('Configured OIDC group claim must be a list.');
        }
        $groups = [];
        foreach ($value as $group) {
            if (!is_string($group) || $group === '' || strlen($group) > 255) {
                throw new OidcValidationException('OIDC group claim contains an invalid value.');
            }
            $groups[$group] = $group;
        }
        return array_values($groups);
    }
}
