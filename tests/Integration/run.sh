#!/bin/sh
set -eu

root=$(CDPATH= cd -- "$(dirname -- "$0")/../.." && pwd)
runtime=${E2E_RUNTIME_DIR:-"$root/.e2e-runtime"}
archive=${E2E_RELEASE_ARCHIVE:-"$runtime/oidc_sso.tar.gz"}
export E2E_RUNTIME_DIR=$runtime
export E2E_RELEASE_ARCHIVE=$archive
compose='docker compose -f tests/Integration/compose.yaml'

cleanup() {
  status=$?
  $compose logs --no-color > "$runtime/compose.log" 2>&1 || true
  $compose down --volumes --remove-orphans || true
  exit "$status"
}
trap cleanup EXIT HUP INT TERM

mkdir -p "$runtime"
sh tests/Integration/generate-runtime.sh "$runtime"
if test ! -f "$archive"; then
  sh scripts/build-release.sh "$archive"
fi

$compose up -d db oidc app civicrm
sh tests/Integration/setup-civicrm.sh
$compose run --rm browser sh -lc 'npm ci && npx playwright test'
sh tests/Integration/verify-civicrm.sh
printf '%s\n' 'FULL STACK E2E OK'
