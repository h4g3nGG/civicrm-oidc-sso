<?php

declare(strict_types=1);

namespace Civi\OidcSso\Web;

use Civi\OidcSso\Protocol\AuthorizationRequestBuilder;
use Civi\OidcSso\Protocol\AuthorizationTransactionManager;
use Civi\OidcSso\Protocol\ProviderMetadataSource;

final class LoginStartService
{
    /** @param list<string> $scopes */
    public function __construct(
        private readonly ProviderMetadataSource $metadataSource,
        private readonly AuthorizationTransactionManager $transactions,
        private readonly string $issuer,
        private readonly string $clientId,
        private readonly string $redirectUri,
        private readonly array $scopes,
    ) {
    }

    public function start(string $returnPath, string $browserBinding, int $now): string
    {
        $metadata = $this->metadataSource->fetch($this->issuer);
        $transaction = $this->transactions->begin($returnPath, $browserBinding, $now);

        return (new AuthorizationRequestBuilder(
            $metadata,
            $this->clientId,
            $this->redirectUri,
            $this->scopes,
        ))->build($transaction);
    }
}
