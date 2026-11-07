<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Web;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Web\CallbackFailureClassifier;
use PHPUnit\Framework\TestCase;

final class CallbackFailureClassifierTest extends TestCase
{
    public function testMapsControlledProtocolStagesWithoutReturningMessages(): void
    {
        $classifier = new CallbackFailureClassifier();

        self::assertSame('token_endpoint_failed', $classifier->classify(
            new OidcValidationException('OIDC token endpoint rejected the request.')
        ));
        self::assertSame('token_signature_failed', $classifier->classify(
            new OidcValidationException('ID token signature or temporal claims are invalid.')
        ));
        self::assertSame('token_claims_failed', $classifier->classify(
            new OidcValidationException('ID token issuer does not match the configured issuer.')
        ));
        self::assertSame('transaction_validation_failed', $classifier->classify(
            new OidcValidationException('OIDC login transaction has expired.')
        ));
        self::assertSame('provider_http_failed', $classifier->classify(
            new OidcValidationException('OpenID Connect HTTP request failed.')
        ));
        self::assertSame('identity_claims_failed', $classifier->classify(
            new OidcValidationException('OIDC email address must be verified before provisioning.')
        ));
        self::assertSame('authorization_claims_failed', $classifier->classify(
            new OidcValidationException('OIDC identity has no explicitly mapped local role.')
        ));
        self::assertSame('authorization_response_failed', $classifier->classify(
            new OidcValidationException('The OpenID Provider rejected the login request.')
        ));
        self::assertSame('protocol_validation_failed', $classifier->classify(
            new OidcValidationException('Unclassified controlled validation failure.')
        ));
    }
}
