#!/bin/sh
set -eu

compose='docker compose -f tests/Integration/compose.yaml'
$compose exec -T -u www-data app cv ev '
  $users = civicrm_api4("User", "get", [
    "checkPermissions" => false,
    "select" => ["id", "contact_id", "roles"],
    "where" => [["username", "=", "e2e.staff"]],
  ])->getArrayCopy();
  if (count($users) !== 1) {
    throw new RuntimeException("Expected exactly one provisioned E2E user.");
  }
  $roles = $users[0]["roles"] ?? [];
  if (is_string($roles)) {
    $roles = array_filter(explode(",", $roles));
  }
  if (in_array(3, array_map("intval", $roles), true)) {
    throw new RuntimeException("Extension-managed role 3 was not removed after an unmapped login.");
  }
  $contacts = civicrm_api4("Contact", "get", [
    "checkPermissions" => false,
    "where" => [["id", "=", $users[0]["contact_id"]]],
  ]);
  if (count($contacts) !== 1) {
    throw new RuntimeException("Provisioned user contact is missing or duplicated.");
  }
  echo "E2E USER AND ROLE STATE OK", PHP_EOL;
' --cwd=/var/www/html
$compose exec -T -u www-data app cv scr \
  /var/www/html/ext/oidc_sso/scripts/verify-audit-log.php \
  --cwd=/var/www/html
