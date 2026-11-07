#!/bin/sh
set -eu

test "$#" -eq 1
runtime=$1
umask 077
mkdir -p "$runtime/tls" "$runtime/secrets" "$runtime/browser-results"

openssl req -x509 -newkey rsa:2048 -nodes -days 1 \
  -subj '/CN=CiviCRM OIDC E2E CA' \
  -keyout "$runtime/tls/ca.key" \
  -out "$runtime/tls/ca.crt"
openssl req -new -newkey rsa:2048 -nodes \
  -subj '/CN=civicrm' \
  -addext 'subjectAltName=DNS:civicrm,DNS:oidc' \
  -keyout "$runtime/tls/server.key" \
  -out "$runtime/tls/server.csr"
openssl x509 -req -days 1 \
  -in "$runtime/tls/server.csr" \
  -CA "$runtime/tls/ca.crt" \
  -CAkey "$runtime/tls/ca.key" \
  -CAcreateserial \
  -copy_extensions copy \
  -out "$runtime/tls/server.crt"
openssl pkcs12 -export \
  -inkey "$runtime/tls/server.key" \
  -in "$runtime/tls/server.crt" \
  -certfile "$runtime/tls/ca.crt" \
  -passout pass:e2e-keystore-only \
  -out "$runtime/tls/server.p12"

printf '%s\n' 'e2e-client-secret' > "$runtime/secrets/client_secret"
openssl rand -base64 48 > "$runtime/secrets/transaction_key"
openssl rand -base64 48 > "$runtime/secrets/audit_key"
chmod 0555 "$runtime/secrets"
chmod 0444 "$runtime/tls/ca.crt" "$runtime/tls/server.crt" \
  "$runtime/tls/server.key" "$runtime/tls/server.p12" "$runtime/secrets/"*
