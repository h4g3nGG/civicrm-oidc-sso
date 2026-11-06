<?php

declare(strict_types=1);

use Civi\OidcSso\Civi\CiviServiceFactory;
use Civi\OidcSso\Audit\AuditEvent;

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
            $identity = CiviServiceFactory::identityProvisioner()->provision($authenticated->claims);
            CiviServiceFactory::roleSynchronizer()->synchronize($identity->userId, $authenticated->claims);
            $redirect = CiviServiceFactory::sessionRedirectBuilder()->build(
                $identity->contactId,
                $authenticated->returnPath,
                time(),
            );
            CiviServiceFactory::audit()->append(
                AuditEvent::AUTHENTICATION,
                AuditEvent::SUCCESS,
                $identity->userId,
            );
        } catch (Throwable $exception) {
            Civi::log()->warning('OpenID Connect callback was rejected ({exception_class}).', [
                'exception_class' => $exception::class,
            ]);
            try {
                CiviServiceFactory::audit()->append(
                    AuditEvent::AUTHENTICATION,
                    AuditEvent::REJECTED,
                    null,
                    ['reason_code' => $this->reasonCode($exception)],
                );
            } catch (Throwable $auditException) {
                Civi::log()->error('OpenID Connect audit write failed ({exception_class}).', [
                    'exception_class' => $auditException::class,
                ]);
            }
            CRM_Core_Error::statusBounce(ts('OpenID Connect login failed. Please start the login again.'));
            return;
        }
        CRM_Utils_System::redirect($redirect);
    }

    private function reasonCode(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof \Civi\OidcSso\Exception\OidcValidationException => 'protocol_validation_failed',
            $exception instanceof InvalidArgumentException => 'invalid_request',
            $exception instanceof RuntimeException => 'runtime_failure',
            default => 'unexpected_failure',
        };
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
