<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Authorization\ClaimGroupExtractor;
use Civi\OidcSso\Authorization\ClaimRoleSynchronizer;
use Civi\OidcSso\Authorization\RoleMapper;
use Civi\OidcSso\Authorization\RoleOwnershipMigrator;
use Civi\OidcSso\Authorization\RoleReconciler;
use Civi\OidcSso\Audit\AuditEventWriter;
use Civi\OidcSso\Protocol\AuthorizationTransactionFactory;
use Civi\OidcSso\Protocol\AuthorizationTransactionManager;
use Civi\OidcSso\Protocol\DiscoveryUrlBuilder;
use Civi\OidcSso\Protocol\IdTokenClaimsValidator;
use Civi\OidcSso\Protocol\JsonDocumentFetcher;
use Civi\OidcSso\Protocol\JwtSignatureVerifier;
use Civi\OidcSso\Protocol\RemoteJwksSource;
use Civi\OidcSso\Protocol\RemoteProviderMetadataSource;
use Civi\OidcSso\Protocol\RotatingIdTokenVerifier;
use Civi\OidcSso\Protocol\StoredAuthorizationTransactionCodec;
use Civi\OidcSso\Protocol\TokenEndpointClient;
use Civi\OidcSso\Protocol\TokenResponseValidator;
use Civi\OidcSso\Identity\ClaimsProfileExtractor;
use Civi\OidcSso\Identity\IdentityProvisioner;
use Civi\OidcSso\Session\AuthxSessionRedirectBuilder;
use Civi\OidcSso\Web\AuthorizationCallbackService;
use Civi\OidcSso\Web\LoginStartService;
use GuzzleHttp\Client;
use RuntimeException;

final class CiviServiceFactory
{
    public static function loginStart(): LoginStartService
    {
        self::assertEnabled();
        $configuration = self::configuration();
        [$metadataSource, $transactions] = self::commonServices();

        return new LoginStartService(
            $metadataSource,
            $transactions,
            $configuration['issuer'],
            $configuration['client_id'],
            $configuration['redirect_uri'],
            $configuration['scopes'],
        );
    }

    public static function callback(): AuthorizationCallbackService
    {
        self::assertEnabled();
        $configuration = self::configuration();
        [$metadataSource, $transactions, $transport] = self::commonServices();
        $metadata = $metadataSource->fetch($configuration['issuer']);
        $secret = null;
        if ($configuration['client_authentication'] !== TokenEndpointClient::AUTH_NONE) {
            $secret = self::secretFromConstant('CIVICRM_OIDC_SSO_CLIENT_SECRET_FILE', 'OIDC client secret');
        }

        return new AuthorizationCallbackService(
            $transactions,
            new TokenEndpointClient(
                $metadata,
                $transport,
                new TokenResponseValidator(),
                $configuration['client_id'],
                $configuration['redirect_uri'],
                $configuration['client_authentication'],
                $secret,
            ),
            new RotatingIdTokenVerifier(
                new JwtSignatureVerifier($metadata->idTokenSigningAlgorithms),
                new RemoteJwksSource(new JsonDocumentFetcher($transport), $metadata->jwksUri),
            ),
            new IdTokenClaimsValidator($configuration['issuer'], $configuration['client_id']),
        );
    }

    public static function identityProvisioner(): IdentityProvisioner
    {
        $api = new CiviApi4Client();
        return new IdentityProvisioner(
            new CiviExternalIdentityRepository(),
            new CiviStandaloneAccountGateway($api),
            new ClaimsProfileExtractor(),
            new CiviProvisioningTransaction(),
            self::audit(),
        );
    }

    public static function roleSynchronizer(): ClaimRoleSynchronizer
    {
        $claim = (string) \Civi::settings()->get('oidc_sso_group_claim');
        $mapping = RoleMappingSetting::decode(\Civi::settings()->get('oidc_sso_role_mapping'));
        $api = new CiviApi4Client();
        return new ClaimRoleSynchronizer(
            new ClaimGroupExtractor($claim),
            new RoleMapper($mapping),
            new RoleReconciler(
                new CiviStandaloneRoleGateway($api),
                new CiviManagedRoleRepository(),
                self::audit(),
            ),
        );
    }

    public static function audit(): AuditEventWriter
    {
        return new CiviAuditEventWriter(self::secretFromConstant(
            'CIVICRM_OIDC_SSO_AUDIT_KEY_FILE',
            'OIDC audit HMAC key',
        ));
    }

    public static function roleOwnershipMigrator(): RoleOwnershipMigrator
    {
        $api = new CiviApi4Client();
        return new RoleOwnershipMigrator(
            new CiviStandaloneRoleGateway($api),
            new CiviManagedRoleRepository(),
        );
    }

    public static function sessionRedirectBuilder(): AuthxSessionRedirectBuilder
    {
        return new AuthxSessionRedirectBuilder(new CiviJwtEncoder());
    }

    /** @return array{RemoteProviderMetadataSource, AuthorizationTransactionManager, GuzzleHttpTransport} */
    private static function commonServices(): array
    {
        $transport = new GuzzleHttpTransport(new Client());
        $metadataSource = new RemoteProviderMetadataSource(
            new JsonDocumentFetcher($transport),
            new DiscoveryUrlBuilder(),
        );
        $transactionKey = self::secretFromConstant(
            'CIVICRM_OIDC_SSO_TRANSACTION_KEY_FILE',
            'OIDC transaction key',
        );
        $transactions = new AuthorizationTransactionManager(
            new AuthorizationTransactionFactory(),
            new CiviAuthorizationTransactionRepository(
                new StoredAuthorizationTransactionCodec(new CiviPayloadCipher()),
            ),
            $transactionKey,
        );
        return [$metadataSource, $transactions, $transport];
    }

    /** @return array{issuer: string, client_id: string, redirect_uri: string, scopes: list<string>, client_authentication: string} */
    private static function configuration(): array
    {
        $settings = \Civi::settings();
        $issuer = trim((string) $settings->get('oidc_sso_issuer'));
        $clientId = trim((string) $settings->get('oidc_sso_client_id'));
        $redirectUri = trim((string) $settings->get('oidc_sso_redirect_uri'));
        $scopeString = trim((string) $settings->get('oidc_sso_scopes'));
        $authentication = (string) $settings->get('oidc_sso_client_authentication');
        if ($issuer === '' || $clientId === '' || $redirectUri === '' || $scopeString === '') {
            throw new RuntimeException('OpenID Connect configuration is incomplete.');
        }
        /** @var list<string> $scopes */
        $scopes = preg_split('/\s+/', $scopeString) ?: [];
        return [
            'issuer' => $issuer,
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scopes' => $scopes,
            'client_authentication' => $authentication,
        ];
    }

    private static function assertEnabled(): void
    {
        if (\Civi::settings()->get('oidc_sso_enabled') !== true) {
            throw new RuntimeException('OpenID Connect login is disabled.');
        }
    }

    private static function secretFromConstant(string $name, string $description): string
    {
        if (!defined($name)) {
            throw new RuntimeException("{$description} file constant is not configured.");
        }
        $path = constant($name);
        if (!is_string($path)) {
            throw new RuntimeException("{$description} file constant must be a path string.");
        }
        return (new SecretFileReader())->read($path, $description);
    }
}
