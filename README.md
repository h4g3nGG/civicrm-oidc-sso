# OpenID Connect SSO for CiviCRM

`civicrm-oidc-sso` is a provider-neutral CiviCRM extension for secure browser
single sign-on using OpenID Connect (OIDC). The project aims to support
just-in-time user provisioning and deterministic mapping of trusted OIDC group
claims to existing CiviCRM roles without modifying CiviCRM core.

> **Development status:** design and implementation in progress. Do not use in
> production yet.

## Goals

- Authorization Code Flow with PKCE.
- Complete validation of ID tokens, including signature, issuer, audience,
  authorized party, expiry, not-before time and nonce.
- CSRF and login-injection protection through one-time `state` and `nonce`.
- Provider-neutral discovery and JWKS support.
- Just-in-time creation and update of CiviCRM users and contacts.
- Stable account linking through the tuple `(issuer, subject)` rather than an
  email address or mutable username.
- Allow-list-based mapping of OIDC groups to pre-existing CiviCRM roles.
- Removal of extension-managed roles when claims change.
- A separately controlled local break-glass login.
- No core patches and no changes to the identity-provider software.
- Auditable configuration, login and role-mapping events.

## Non-goals

- Mirroring every identity-provider user before their first login. OIDC
  describes authentication and claims for the current user; it is not a user
  directory synchronization protocol.
- Automatically creating roles from arbitrary group names received from an
  identity provider.
- Treating an email address as a permanent identity key.
- Replacing the identity provider's access policies or multi-factor
  authentication.

## Planned login flow

1. CiviCRM redirects an unauthenticated browser to the configured OpenID
   Provider using Authorization Code Flow with PKCE.
2. The callback validates the authorization response and the ID token.
3. The extension links or provisions the local user through supported CiviCRM
   APIs.
4. Explicit group-to-role mappings are reconciled.
5. A CiviCRM browser session is established through the documented AuthX
   session flow.

See [Architecture](docs/architecture.md) and [Security model](docs/security.md).

## Implemented security foundation

The current development version includes tested building blocks for:

- validating provider discovery metadata and HTTPS endpoints;
- retrieving discovery documents, JWKS and token responses through a bounded,
  TLS-verifying Guzzle adapter which rejects redirects and non-HTTPS URLs;
- creating authorization requests with one-time state, nonce and PKCE;
- exchanging authorization codes with PKCE and supported OAuth client
  authentication methods, with strict token-response validation;
- managing browser-bound login transactions through an atomic persistence
  contract with HMAC-indexed state, expiry cleanup and one-time consumption;
- installing extension-owned transaction and external-identity tables through
  CiviCRM entity schemas and its AutomaticUpgrader;
- verifying compact JWS ID-token signatures against an algorithm allow list;
- selecting signing keys by `kid` and refreshing JWKS once after key rotation;
- validating issuer, audience, authorized party, nonce and temporal claims;
- reconciling explicitly configured group-to-role mappings while tracking and
  removing only assignments which the extension itself added;
- exposing disabled-by-default CiviCRM login and callback routes which create,
  consume and cryptographically validate a complete OIDC browser transaction;
- creating new Standalone contacts/users through APIv4 or updating profiles
  already linked by the stable `(issuer, subject)` identity tuple; and
- creating a 60-second CiviCRM-signed JWT for the documented AuthX auto-session
  handoff after authentication and authorization succeed.

The protocol flow, user provisioning, role reconciliation and session handoff
have automated coverage, but the complete administration UI, auditable
migration importer and end-to-end browser tests are not yet finished. The
extension is therefore not ready for production use and must remain disabled.

Existing local accounts are never claimed automatically by matching an email
address. They must be linked explicitly before cutover; see
[Migrating existing users](docs/migration.md).

## Configuration model (planned)

Configuration will be managed through CiviCRM settings and an administration
form. Secrets must not be committed to this repository. At minimum, an
installation will configure:

- issuer/discovery URL;
- client ID and client authentication method;
- client secret or private-key reference;
- exact redirect URI;
- requested scopes (`openid`, `profile`, `email`, and a group claim scope);
- claim names for username, display name, email and groups;
- an explicit group-to-role allow list;
- provisioning and deprovisioning policies;
- local break-glass login policy.

## Related projects

- [CiviCRM Core on GitHub](https://github.com/civicrm/civicrm-core)
- [CiviCRM developer documentation](https://docs.civicrm.org/dev/en/latest/)
- [CiviCRM AuthX documentation](https://docs.civicrm.org/dev/en/latest/framework/authx/)
- [CiviCRM OAuth Login (`oauth_login`)](https://lab.civicrm.org/extensions/oauth_login)

The `oauth_login` extension is an important prior experiment and design
reference. This project is a separate implementation with an explicit security
model, complete cryptographic token validation, provider-neutral group claims,
role reconciliation and production-focused tests. No claim is made that the
projects are affiliated.

## Development

The initial code targets maintained CiviCRM 6.x releases and PHP versions
supported by those releases. The repository will use Composer for development
tooling and reproducible third-party dependencies.

```bash
docker compose run --rm test
```

The container installs development dependencies and runs Composer validation,
the coding standard and the unit test suite. The same checks run in Gitea CI
for PHP 8.1 through 8.4.

The extension uses the current civix extension format, the documented
`AutomaticUpgrader`, `entity-types-php` and `setting-php` mixins, and CiviCRM's
credential cryptography service. Remote OpenID Connect endpoints are accessed
with Guzzle, following CiviCRM's documented OAuth integration approach. Runtime
dependencies must be installed before enabling the extension:

```bash
composer install --no-dev --classmap-authoritative
```

Build an installable release archive from the committed source with normalized
web-server-readable permissions:

```bash
sh scripts/build-release.sh
```

The archive contains a single `oidc_sso/` directory. Directories use mode
`0755`, ordinary files use mode `0644`, and continuous integration verifies the
archive root permissions and the presence of the CiviCRM entrypoint.

The host CiviCRM installation must configure a real `CIVICRM_CRED_KEYS` key.
The extension refuses to persist OIDC transaction secrets when CiviCRM falls
back to plain-text credential storage. The OIDC client secret and transaction
HMAC key will be supplied through protected deployment configuration rather
than ordinary database settings.

The login transaction HMAC key and, for confidential clients, the OIDC client
secret are read from files. Deployments define their paths in
`civicrm.settings.php`; the files themselves should be mounted read-only and
must not be stored in the extension directory:

```php
define('CIVICRM_OIDC_SSO_TRANSACTION_KEY_FILE', '/run/secrets/oidc_transaction_key');
define('CIVICRM_OIDC_SSO_CLIENT_SECRET_FILE', '/run/secrets/oidc_client_secret');
```

The transaction key must contain at least 32 bytes. Public clients configured
with token-endpoint authentication method `none` do not use a client-secret
file. The registered redirect URI must exactly match the configured
`oidc_sso_redirect_uri`, normally ending in `/civicrm/oidc/callback`.

Never commit client secrets, private keys, tokens, real user attributes,
deployment hostnames or organization-specific role mappings. Examples and
fixtures must use reserved domains such as `example.org` and synthetic data.

## License

Copyright contributors.

Licensed under the GNU Affero General Public License, version 3 or (at your
option) any later version (`AGPL-3.0-or-later`). See [LICENSE](LICENSE).

