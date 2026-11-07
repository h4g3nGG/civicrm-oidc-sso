#!/bin/sh
set -eu

compose='docker compose -f tests/AuthentikIntegration/compose.yaml'
$compose exec -T -u www-data app cv ev '
  $users = civicrm_api4("User", "get", [
    "checkPermissions" => false,
    "select" => ["id", "contact_id", "roles"],
    "where" => [["username", "=", "e2e.staff"]],
  ])->getArrayCopy();
  if (count($users) !== 1) {
    throw new RuntimeException("Expected exactly one Authentik-provisioned user.");
  }
  $roles = $users[0]["roles"] ?? [];
  if (is_string($roles)) {
    $roles = array_filter(explode(",", $roles));
  }
  if (!in_array(3, array_map("intval", $roles), true)) {
    throw new RuntimeException("Authentik group claim did not assign role 3.");
  }
  echo "AUTHENTIK E2E USER AND ROLE STATE OK", PHP_EOL;
' --cwd=/var/www/html
$compose exec -T -u www-data app cv scr \
  /var/www/html/ext/oidc_sso/scripts/verify-audit-log.php \
  --cwd=/var/www/html
