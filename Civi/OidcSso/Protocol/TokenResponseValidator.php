<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use JsonException;

final class TokenResponseValidator
{
    public function validate(HttpResponse $response): TokenResponse
    {
        if (!$this->hasJsonContentType($response->headers)) {
            throw new OidcValidationException('OIDC token endpoint did not return JSON content.');
        }
        $document = $this->decodeObject($response->body);

        if ($response->statusCode !== 200) {
            $error = $document['error'] ?? null;
            $suffix = is_string($error) && preg_match('/^[A-Za-z0-9._-]+$/D', $error) === 1
                ? " ({$error})"
                : '';
            throw new OidcValidationException("OIDC token endpoint rejected the request{$suffix}.");
        }
        if (isset($document['error'])) {
            throw new OidcValidationException('OIDC token response contains an error.');
        }

        $accessToken = $this->requiredString($document, 'access_token');
        $idToken = $this->requiredString($document, 'id_token');
        $tokenType = $this->requiredString($document, 'token_type');
        if (strcasecmp($tokenType, 'Bearer') !== 0) {
            throw new OidcValidationException("OIDC token type must be 'Bearer'.");
        }

        $expiresIn = $document['expires_in'] ?? null;
        if ($expiresIn !== null && (!is_int($expiresIn) || $expiresIn <= 0)) {
            throw new OidcValidationException("OIDC token field 'expires_in' must be a positive integer.");
        }

        return new TokenResponse(
            $accessToken,
            $idToken,
            $tokenType,
            $expiresIn,
            $this->optionalString($document, 'refresh_token'),
            $this->optionalString($document, 'scope'),
        );
    }

    /** @param array<string, string> $headers */
    private function hasJsonContentType(array $headers): bool
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Content-Type') === 0) {
                return preg_match('#^application/json(?:\s*;|$)#iD', trim($value)) === 1;
            }
        }
        return false;
    }

    /** @return array<string, mixed> */
    private function decodeObject(string $body): array
    {
        if ($body === '' || strlen($body) > 1048576) {
            throw new OidcValidationException('OIDC token response has an invalid size.');
        }
        try {
            $document = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new OidcValidationException('OIDC token response is not valid JSON.', 0, $exception);
        }
        if (!is_array($document) || array_is_list($document)) {
            throw new OidcValidationException('OIDC token response must be a JSON object.');
        }
        return $document;
    }

    /** @param array<string, mixed> $document */
    private function requiredString(array $document, string $name): string
    {
        $value = $document[$name] ?? null;
        if (!is_string($value) || $value === '') {
            throw new OidcValidationException("OIDC token field '{$name}' must be a non-empty string.");
        }
        return $value;
    }

    /** @param array<string, mixed> $document */
    private function optionalString(array $document, string $name): ?string
    {
        if (!array_key_exists($name, $document)) {
            return null;
        }
        return $this->requiredString($document, $name);
    }
}
