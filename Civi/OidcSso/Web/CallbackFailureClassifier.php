<?php

declare(strict_types=1);

namespace Civi\OidcSso\Web;

use Civi\OidcSso\Exception\OidcValidationException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class CallbackFailureClassifier
{
    public function classify(Throwable $exception): string
    {
        if ($exception instanceof OidcValidationException) {
            $message = $exception->getMessage();
            return match (true) {
                str_starts_with($message, 'OIDC token endpoint'),
                str_starts_with($message, 'OIDC token response'),
                str_starts_with($message, 'OIDC token type') => 'token_endpoint_failed',
                str_starts_with($message, 'ID token signature'),
                str_starts_with($message, 'ID token uses'),
                str_starts_with($message, 'ID token contains'),
                str_starts_with($message, 'ID token header'),
                str_starts_with($message, 'ID token payload'),
                str_starts_with($message, 'ID token must'),
                str_starts_with($message, 'Provider returned an invalid JWKS'),
                str_starts_with($message, 'Signing key algorithm') => 'token_signature_failed',
                str_starts_with($message, 'ID token ') => 'token_claims_failed',
                str_starts_with($message, 'OIDC login transaction'),
                str_starts_with($message, 'OIDC callback state'),
                str_starts_with($message, 'Stored OIDC') => 'transaction_validation_failed',
                str_starts_with($message, 'OpenID Connect HTTP'),
                str_starts_with($message, 'OpenID Connect JSON'),
                str_starts_with($message, 'OpenID Connect endpoint') => 'provider_http_failed',
                default => 'protocol_validation_failed',
            };
        }
        return match (true) {
            $exception instanceof InvalidArgumentException => 'invalid_request',
            $exception instanceof RuntimeException => 'runtime_failure',
            default => 'unexpected_failure',
        };
    }
}
