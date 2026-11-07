#!/bin/sh
set -eu

compose='docker compose -f tests/Integration/compose.yaml'

$compose exec -T -u www-data \
  -e CIVICRM_ADMIN_USER=e2e-admin \
  -e CIVICRM_ADMIN_PASS=e2e-admin-password \
  app civicrm-docker-install

$compose exec -T app sh -c '
  rm -rf /var/www/html/ext/oidc_sso
  tar -xzf /run/e2e/oidc_sso.tar.gz -C /var/www/html/ext
  chown -R www-data:www-data /var/www/html/ext/oidc_sso
  cat >> /var/www/html/private/civicrm.settings.php <<"PHP"

define("CIVICRM_OIDC_SSO_CLIENT_SECRET_FILE", "/run/e2e-secrets/client_secret");
define("CIVICRM_OIDC_SSO_TRANSACTION_KEY_FILE", "/run/e2e-secrets/transaction_key");
define("CIVICRM_OIDC_SSO_AUDIT_KEY_FILE", "/run/e2e-secrets/audit_key");
PHP
'

$compose exec -T -u www-data app cv ext:enable oidc_sso --cwd=/var/www/html
$compose exec -T -u www-data app cv upgrade:db --mode=ext --no-interaction --cwd=/var/www/html
$compose exec -T -u www-data app cv ev '
  $roles = civicrm_api4("Role", "get", [
    "checkPermissions" => false,
    "where" => [["id", "=", 3]],
  ]);
  if (count($roles) !== 1) {
    throw new RuntimeException("Expected Standalone role 3 is unavailable.");
  }
  $settings = [
    "oidc_sso_issuer" => "https://oidc:8080/e2e",
    "oidc_sso_client_id" => "civicrm-e2e",
    "oidc_sso_redirect_uri" => "https://civicrm/civicrm/oidc/callback",
    "oidc_sso_client_authentication" => "client_secret_basic",
    "oidc_sso_scopes" => "openid profile email",
    "oidc_sso_group_claim" => "groups",
    "oidc_sso_role_mapping" => "{\"e2e.staff\":[3]}",
    "oidc_sso_enabled" => true,
  ];
  foreach ($settings as $name => $value) {
    Civi::settings()->set($name, $value);
  }
' --cwd=/var/www/html
$compose exec -T -u www-data app cv flush --cwd=/var/www/html
