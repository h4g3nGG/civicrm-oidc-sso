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
$compose exec -T -u www-data app cv ev '
  $baseline = json_decode(
    file_get_contents("/var/www/html/private/e2e-migration-baseline.json"),
    true,
    16,
    JSON_THROW_ON_ERROR
  );
  $users = civicrm_api4("User", "get", [
    "checkPermissions" => false,
    "select" => ["id", "contact_id", "username", "uf_name", "roles"],
    "where" => [["id", "=", $baseline["user_id"]]],
  ])->getArrayCopy();
  if (count($users) !== 1) {
    throw new RuntimeException("Migrated local user is missing or duplicated.");
  }
  $user = $users[0];
  foreach (["id" => "user_id", "contact_id" => "contact_id", "username" => "username", "uf_name" => "email"] as $field => $expected) {
    if ((string) $user[$field] !== (string) $baseline[$expected]) {
      throw new RuntimeException("Migrated local user field changed: {$field}.");
    }
  }
  $roles = $user["roles"] ?? [];
  if (is_string($roles)) {
    $roles = array_filter(explode(",", $roles));
  }
  $roles = array_map("intval", $roles);
  sort($roles);
  if ($roles !== [2]) {
    throw new RuntimeException("Migration did not preserve manual role 2 or remove managed role 3.");
  }
  $contacts = civicrm_api4("Contact", "get", [
    "checkPermissions" => false,
    "select" => ["id", "first_name", "last_name"],
    "where" => [["id", "=", $baseline["contact_id"]]],
  ])->getArrayCopy();
  if (count($contacts) !== 1
      || $contacts[0]["first_name"] !== $baseline["first_name"]
      || $contacts[0]["last_name"] !== $baseline["last_name"]
  ) {
    throw new RuntimeException("Migrated contact identity or profile changed.");
  }
  $identities = civicrm_api4("OidcSsoExternalIdentity", "get", [
    "checkPermissions" => false,
    "where" => [["uf_match_id", "=", $baseline["user_id"]]],
  ]);
  if (count($identities) !== 1) {
    throw new RuntimeException("Migrated user identity link is missing or duplicated.");
  }
  echo "E2E MIGRATION STATE OK", PHP_EOL;
' --cwd=/var/www/html
$compose exec -T -u www-data app cv scr \
  /var/www/html/ext/oidc_sso/scripts/verify-audit-log.php \
  --cwd=/var/www/html
