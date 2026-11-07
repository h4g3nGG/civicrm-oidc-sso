#!/bin/sh

set -eu

test "$#" -eq 1
archive=$1
test -f "$archive"

listing=$(mktemp "${TMPDIR:-/tmp}/civicrm-oidc-archive-list.XXXXXX")
verbose=$(mktemp "${TMPDIR:-/tmp}/civicrm-oidc-archive-verbose.XXXXXX")
trap 'rm -f "$listing" "$verbose"' EXIT HUP INT TERM

tar -tzf "$archive" > "$listing"
tar -tvzf "$archive" > "$verbose"

if gzip -dc "$archive" | grep -a -q 'LIBARCHIVE\.xattr\|com\.apple\.'; then
  echo 'Archive contains platform-specific extended attributes.' >&2
  exit 1
fi

awk '
  $0 !~ /^oidc_sso\// { print "Archive path is outside oidc_sso/: " $0; failed=1 }
  $0 ~ /(^|\/)\.\.($|\/)/ { print "Archive path contains traversal: " $0; failed=1 }
  $0 ~ /(^|\/)\._[^\/]*\/?$/ { print "Archive contains AppleDouble metadata: " $0; failed=1 }
  $0 ~ /(^|\/)\.DS_Store$/ { print "Archive contains Finder metadata: " $0; failed=1 }
  END { exit failed }
' "$listing"

if awk 'substr($1, 1, 1) == "l" { print; found=1 } END { exit !found }' "$verbose"; then
  echo 'Archive must not contain symbolic links.' >&2
  exit 1
fi

mode=$(awk '$NF == "oidc_sso/" { print $1; exit }' "$verbose")
test "$mode" = "drwxr-xr-x"
grep -qx 'oidc_sso/oidc_sso.php' "$listing"
grep -qx 'oidc_sso/info.xml' "$listing"
grep -qx 'oidc_sso/vendor/autoload.php' "$listing"

printf '%s\n' 'RELEASE ARCHIVE OK'
