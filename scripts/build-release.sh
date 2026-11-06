#!/bin/sh

set -eu

root_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
version=$(sed -n 's:.*<version>\([^<]*\)</version>.*:\1:p' "$root_dir/info.xml" | head -n 1)
test -n "$version"

output=${1:-"$root_dir/dist/civicrm-oidc-sso-${version}.tar.gz"}
case "$output" in
  /*) ;;
  *) output="$PWD/$output" ;;
esac

stage_root=$(mktemp -d "${TMPDIR:-/tmp}/civicrm-oidc-sso-release.XXXXXX")
trap 'rm -rf "$stage_root"' EXIT HUP INT TERM
stage_dir="$stage_root/oidc_sso"
mkdir -p "$stage_dir"

git -C "$root_dir" archive HEAD | tar -x -C "$stage_dir"
composer --working-dir="$stage_dir" install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --classmap-authoritative

find "$stage_dir" -type d -exec chmod 0755 {} +
find "$stage_dir" -type f -exec chmod 0644 {} +
chmod 0755 "$stage_dir/scripts/build-release.sh"

mkdir -p "$(dirname -- "$output")"
tar -C "$stage_root" -czf "$output" oidc_sso

printf '%s\n' "$output"
