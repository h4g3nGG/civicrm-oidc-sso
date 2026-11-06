<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\UnknownSigningKeyException;

final class RotatingIdTokenVerifier implements VerifiedIdTokenDecoder
{
    public function __construct(
        private readonly IdTokenSignatureVerifier $signatureVerifier,
        private readonly JwksSource $jwksSource,
    ) {
    }

    /** @return array<string, mixed> */
    public function verify(string $jwt): array
    {
        try {
            return $this->signatureVerifier->verify($jwt, $this->jwksSource->get());
        } catch (UnknownSigningKeyException) {
            // A provider may rotate keys between cache refreshes. Force exactly
            // one refresh; any subsequent miss is a hard authentication error.
            return $this->signatureVerifier->verify($jwt, $this->jwksSource->get(true));
        }
    }
}
