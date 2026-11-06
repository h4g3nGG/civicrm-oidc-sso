<?php

declare(strict_types=1);

use Civi\OidcSso\Civi\CiviServiceFactory;

final class CRM_OidcSso_Page_Callback extends CRM_Core_Page
{
    private const BINDING_COOKIE = 'civicrm_oidc_sso_binding';

    public function run(): void
    {
        try {
            $state = $this->requiredQueryString('state');
            $binding = $this->requiredCookie(self::BINDING_COOKIE);
            $authenticated = CiviServiceFactory::callback()->complete(
                $state,
                $this->queryString('code'),
                $this->queryString('error'),
                $binding,
                time(),
            );
            CiviServiceFactory::identityProvisioner()->provision($authenticated->claims);
        } catch (Throwable $exception) {
            Civi::log()->warning('OpenID Connect callback was rejected: {message}', [
                'message' => $exception->getMessage(),
            ]);
            CRM_Core_Error::statusBounce(ts('OpenID Connect login failed. Please start the login again.'));
        }

        // The route is installed ahead of provisioning and AuthX session
        // bridging, but cannot be reached while the extension is disabled.
        CRM_Core_Error::statusBounce(ts('OpenID Connect account provisioning is not enabled yet.'));
    }

    private function requiredQueryString(string $name): string
    {
        $value = $this->queryString($name);
        if ($value === null || $value === '') {
            throw new InvalidArgumentException("Missing callback parameter '{$name}'.");
        }
        return $value;
    }

    private function queryString(string $name): ?string
    {
        $value = $_GET[$name] ?? null;
        return is_string($value) ? $value : null;
    }

    private function requiredCookie(string $name): string
    {
        $value = $_COOKIE[$name] ?? null;
        if (!is_string($value) || preg_match('/^[A-Za-z0-9_-]{43}$/D', $value) !== 1) {
            throw new InvalidArgumentException('OIDC browser binding cookie is missing or invalid.');
        }
        return $value;
    }
}
