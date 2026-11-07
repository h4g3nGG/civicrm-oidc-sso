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
$compose exec -T -u www-data app cv ev '
  $roles = civicrm_api4("Role", "get", [
    "checkPermissions" => false,
    "where" => [["id", "IN", [2, 3]]],
  ])->getArrayCopy();
  if (count($roles) !== 2) {
    throw new RuntimeException("Expected Standalone roles 2 and 3 are unavailable.");
  }
  $contact = civicrm_api4("Contact", "create", [
    "checkPermissions" => false,
    "values" => [
      "contact_type" => "Individual",
      "first_name" => "Existing",
      "last_name" => "Local",
    ],
  ])->getArrayCopy()[0];
  civicrm_api4("Email", "create", [
    "checkPermissions" => false,
    "values" => [
      "contact_id" => $contact["id"],
      "email" => "migrated@example.test",
      "is_primary" => true,
    ],
  ]);
  $user = civicrm_api4("User", "create", [
    "checkPermissions" => false,
    "values" => [
      "username" => "e2e.migrated",
      "uf_name" => "migrated@example.test",
      "contact_id" => $contact["id"],
      "password" => bin2hex(random_bytes(24)),
      "is_active" => true,
      "roles" => [2, 3],
    ],
  ])->getArrayCopy()[0];
  $manifest = [
    "issuer" => "https://oidc:8080/e2e",
    "entries" => [[
      "subject" => "e2e-migrated-subject",
      "user_id" => (int) $user["id"],
      "managed_role_ids" => [3],
    ]],
  ];
  $baseline = [
    "user_id" => (int) $user["id"],
    "contact_id" => (int) $contact["id"],
    "username" => "e2e.migrated",
    "email" => "migrated@example.test",
    "first_name" => "Existing",
    "last_name" => "Local",
  ];
  file_put_contents(
    "/var/www/html/private/e2e-migration.json",
    json_encode($manifest, JSON_THROW_ON_ERROR)
  );
  file_put_contents(
    "/var/www/html/private/e2e-migration-baseline.json",
    json_encode($baseline, JSON_THROW_ON_ERROR)
  );
  chmod("/var/www/html/private/e2e-migration.json", 0600);
  chmod("/var/www/html/private/e2e-migration-baseline.json", 0600);
' --cwd=/var/www/html
$compose exec -T -u www-data app cv scr \
  /var/www/html/ext/oidc_sso/scripts/migrate-existing-users.php \
  --cwd=/var/www/html -- \
  --manifest=/var/www/html/private/e2e-migration.json
$compose exec -T -u www-data app cv ev '
  $identityCount = CRM_Core_DAO::singleValueQuery(
    "SELECT COUNT(*) FROM civicrm_oidc_sso_identity"
  );
  $managedCount = CRM_Core_DAO::singleValueQuery(
    "SELECT COUNT(*) FROM civicrm_oidc_sso_managed_role"
  );
  if ((int) $identityCount !== 0 || (int) $managedCount !== 0) {
    throw new RuntimeException("Migration dry-run modified identity or role ownership.");
  }
  echo "MIGRATION DRY-RUN STATE OK\n";
' --cwd=/var/www/html
$compose exec -T -u www-data app cv scr \
  /var/www/html/ext/oidc_sso/scripts/migrate-existing-users.php \
  --cwd=/var/www/html -- \
  --manifest=/var/www/html/private/e2e-migration.json --apply
$compose exec -T -u www-data app cv ev '
  $url = Civi\OidcSso\Civi\CiviServiceFactory::loginStart()->start(
    "/civicrm/home",
    str_repeat("a", 43),
    time()
  );
  if (!str_starts_with($url, "https://oidc:8080/e2e/authorize?")) {
    throw new RuntimeException("OIDC preflight returned an unexpected authorization URL.");
  }
  echo "OIDC PREFLIGHT OK\n";
' --cwd=/var/www/html
