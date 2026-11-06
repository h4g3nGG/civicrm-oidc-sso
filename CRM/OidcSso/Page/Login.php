<?php

declare(strict_types=1);

use Civi\OidcSso\Civi\CiviServiceFactory;

final class CRM_OidcSso_Page_Login extends CRM_Core_Page
{
    private const BINDING_COOKIE = 'civicrm_oidc_sso_binding';

    public function run(): void
    {
        try {
            $binding = $this->browserBinding();
            $returnPath = $this->queryString('return') ?? '/civicrm/home';
            $url = CiviServiceFactory::loginStart()->start($returnPath, $binding, time());
            CRM_Utils_System::redirect($url);
        } catch (Throwable $exception) {
            Civi::log()->error('OpenID Connect login could not be started: {message}', [
                'message' => $exception->getMessage(),
            ]);
            CRM_Core_Error::statusBounce(ts('OpenID Connect login is currently unavailable.'));
        }
    }

    private function browserBinding(): string
    {
        $existing = $_COOKIE[self::BINDING_COOKIE] ?? null;
        if (is_string($existing) && preg_match('/^[A-Za-z0-9_-]{43}$/D', $existing) === 1) {
            return $existing;
        }
        $binding = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        setcookie(self::BINDING_COOKIE, $binding, [
            'expires' => 0,
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        return $binding;
    }

    private function queryString(string $name): ?string
    {
        $value = $_GET[$name] ?? null;
        return is_string($value) ? $value : null;
    }
}
