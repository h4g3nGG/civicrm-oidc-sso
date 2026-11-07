#!/bin/sh
set -eu

root=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
runtime=${E2E_RUNTIME_DIR:-"$root/.authentik-e2e-runtime"}
archive=${E2E_RELEASE_ARCHIVE:-"$runtime/oidc_sso.tar.gz"}
export E2E_RUNTIME_DIR=$runtime
export E2E_RELEASE_ARCHIVE=$archive
compose='docker compose -f tests/AuthentikIntegration/compose.yaml'

cleanup() {
  status=$?
  $compose down --volumes --remove-orphans || true
  exit "$status"
}
trap cleanup EXIT HUP INT TERM

mkdir -p "$runtime"
sh tests/Integration/generate-runtime.sh "$runtime"
mkdir -p "$runtime/authentik-secrets"
printf '%s\n' 'e2e-authentik-postgres' > "$runtime/authentik-secrets/postgres_password"
openssl rand -base64 60 > "$runtime/authentik-secrets/secret_key"
chmod 0555 "$runtime/authentik-secrets"
chmod 0444 "$runtime/authentik-secrets/"*
if test ! -f "$archive"; then
  sh scripts/build-release.sh "$archive"
fi

$compose up -d db authentik-db authentik-server authentik-worker app gateway
i=0
until $compose exec -T gateway wget -q --no-check-certificate -O /dev/null \
  https://authentik/application/o/civicrm-e2e/.well-known/openid-configuration; do
  i=$((i + 1))
  if test "$i" -ge 90; then
    printf '%s\n' 'Authentik blueprint or discovery endpoint did not become ready.' >&2
    exit 1
  fi
  sleep 2
done
sh tests/AuthentikIntegration/setup-civicrm.sh
if ! $compose run --rm browser sh -lc \
  'cp -R /source/. /work/ && cp /authentik-flow.spec.js /work/ && npm ci && npx playwright test'; then
  printf '%s\n' 'Sanitized CiviCRM OIDC start diagnostics:' >&2
  $compose logs --no-color app 2>/dev/null \
    | grep "OpenID Connect login could not be started" \
    | tail -n 3 >&2 || true
  printf '%s\n' 'Controlled callback audit reason:' >&2
  $compose exec -T -u www-data app cv ev '
    $json = CRM_Core_DAO::singleValueQuery(
      "SELECT details_json FROM civicrm_oidc_sso_audit_event"
      . " WHERE event_type = %1 AND outcome = %2"
      . " ORDER BY id DESC LIMIT 1",
      [1 => ["authentication", "String"], 2 => ["rejected", "String"]]
    );
    $details = is_string($json) ? json_decode($json, true) : null;
    $reason = is_array($details) ? ($details["reason_code"] ?? null) : null;
    $allowed = [
      "protocol_validation_failed", "invalid_request",
      "runtime_failure", "unexpected_failure",
    ];
    echo is_string($reason) && in_array($reason, $allowed, true)
      ? $reason : "unavailable", PHP_EOL;
  ' --cwd=/var/www/html >&2 || true
  exit 1
fi
sh tests/AuthentikIntegration/verify-civicrm.sh
printf '%s\n' 'AUTHENTIK FULL STACK E2E OK'
