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
                str_starts_with($message, 'OIDC email'),
                str_starts_with($message, 'OIDC preferred username'),
                str_starts_with($message, 'OIDC profile'),
                str_starts_with($message, 'OIDC claim') => 'identity_claims_failed',
                str_starts_with($message, 'Configured OIDC group'),
                str_starts_with($message, 'OIDC group'),
                str_starts_with($message, 'OIDC identity has no') => 'authorization_claims_failed',
                str_starts_with($message, 'The OpenID Provider rejected'),
                str_starts_with($message, 'OIDC callback does not') => 'authorization_response_failed',
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
