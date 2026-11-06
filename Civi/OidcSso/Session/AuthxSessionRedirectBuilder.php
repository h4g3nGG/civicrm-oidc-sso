<?php

declare(strict_types=1);

namespace Civi\OidcSso\Session;

use InvalidArgumentException;

final class AuthxSessionRedirectBuilder
{
    public function __construct(
        private readonly JwtEncoder $encoder,
        private readonly int $lifetimeSeconds = 60,
    ) {
        if ($lifetimeSeconds < 15 || $lifetimeSeconds > 120) {
            throw new InvalidArgumentException('AuthX handoff lifetime must be between 15 and 120 seconds.');
        }
    }

    public function build(int $contactId, string $returnPath, int $now): string
    {
        if ($contactId < 1 || !$this->isSafeLocalPath($returnPath)) {
            throw new InvalidArgumentException('AuthX session handoff input is invalid.');
        }
        $token = $this->encoder->encode([
            'sub' => 'cid:' . $contactId,
            'scope' => 'authx',
            'iat' => $now,
            'exp' => $now + $this->lifetimeSeconds,
            'jti' => $this->base64Url(random_bytes(24)),
        ]);
        if ($token === '') {
            throw new InvalidArgumentException('AuthX JWT encoder returned an empty token.');
        }
        $query = http_build_query([
            '_authx' => 'Bearer ' . $token,
            '_authxSes' => '1',
        ], '', '&', PHP_QUERY_RFC3986);
        return $returnPath . (str_contains($returnPath, '?') ? '&' : '?') . $query;
    }

    private function isSafeLocalPath(string $path): bool
    {
        return str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && !str_contains($path, "\r")
            && !str_contains($path, "\n")
            && !str_contains($path, '#')
            && filter_var($path, FILTER_VALIDATE_URL) === false;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
