<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Exception\UnknownSigningKeyException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Throwable;

final class JwtSignatureVerifier implements IdTokenSignatureVerifier
{
    /** @param list<string> $allowedAlgorithms */
    public function __construct(private readonly array $allowedAlgorithms)
    {
        if ($allowedAlgorithms === [] || in_array('none', $allowedAlgorithms, true)) {
            throw new OidcValidationException('At least one secure JWT signing algorithm must be allowed.');
        }
    }

    /**
     * @param array<string, mixed> $jwks
     * @return array<string, mixed>
     */
    public function verify(string $jwt, array $jwks): array
    {
        $header = $this->decodeHeader($jwt);
        $algorithm = $this->requiredHeaderString($header, 'alg');
        $keyId = $this->requiredHeaderString($header, 'kid');

        if (!in_array($algorithm, $this->allowedAlgorithms, true)) {
            throw new OidcValidationException('ID token uses a signing algorithm which is not allowed.');
        }
        if (isset($header['crit'])) {
            throw new OidcValidationException('ID token contains unsupported critical header parameters.');
        }

        try {
            $keys = JWK::parseKeySet($jwks, $algorithm);
        } catch (Throwable $exception) {
            throw new OidcValidationException('Provider returned an invalid JWKS document.', 0, $exception);
        }
        if (!isset($keys[$keyId])) {
            throw new UnknownSigningKeyException('ID token references an unknown signing key.');
        }
        if (!hash_equals($algorithm, $keys[$keyId]->getAlgorithm())) {
            throw new OidcValidationException('Signing key algorithm does not match the ID-token header.');
        }

        try {
            $claims = JWT::decode($jwt, [$keyId => $keys[$keyId]]);
            $encoded = json_encode($claims, JSON_THROW_ON_ERROR);
            $decoded = json_decode($encoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new OidcValidationException('ID token signature or temporal claims are invalid.', 0, $exception);
        }
        if (!is_array($decoded)) {
            throw new OidcValidationException('ID token payload must be a JSON object.');
        }
        return $decoded;
    }

    /** @return array<string, mixed> */
    private function decodeHeader(string $jwt): array
    {
        $segments = explode('.', $jwt);
        if (count($segments) !== 3 || in_array('', $segments, true)) {
            throw new OidcValidationException('ID token must use compact JWS serialization.');
        }
        $encoded = $segments[0];
        if (preg_match('/^[A-Za-z0-9_-]+$/D', $encoded) !== 1) {
            throw new OidcValidationException('ID token header is not valid base64url.');
        }
        $padding = (4 - strlen($encoded) % 4) % 4;
        $json = base64_decode(strtr($encoded . str_repeat('=', $padding), '-_', '+/'), true);
        if ($json === false) {
            throw new OidcValidationException('ID token header is not valid base64url.');
        }
        try {
            $header = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new OidcValidationException('ID token header is not valid JSON.', 0, $exception);
        }
        if (!is_array($header) || array_is_list($header)) {
            throw new OidcValidationException('ID token header must be a JSON object.');
        }
        return $header;
    }

    /** @param array<string, mixed> $header */
    private function requiredHeaderString(array $header, string $name): string
    {
        if (!isset($header[$name]) || !is_string($header[$name]) || $header[$name] === '') {
            throw new OidcValidationException("ID token header '{$name}' must be a non-empty string.");
        }
        return $header[$name];
    }
}
