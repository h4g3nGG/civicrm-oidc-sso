# OpenID Connect SSO for CiviCRM

`civicrm-oidc-sso` is a provider-neutral CiviCRM extension for secure browser
single sign-on using OpenID Connect (OIDC). The project aims to support
just-in-time user provisioning and deterministic mapping of trusted OIDC group
claims to existing CiviCRM roles without modifying CiviCRM core.

> **Development status:** `0.3.0-beta.4` is a public evaluation release. It is
> not approved for production use and has not completed an independent
> security review. See the
> [changelog](CHANGELOG.md) for release scope and known limitations.

## Origin and support

This extension was originally developed for the
[hospice in Groß-Gerau](https://hospiz-gg.de/), Germany. If you find the
project useful and would like to say thank you, a
[donation to the hospice via PayPal](https://www.paypal.com/donate/?hosted_button_id=NHAECADL3F3E8)
is warmly appreciated.

For technical help, see [Support](SUPPORT.md). Report suspected vulnerabilities
privately according to the [Security policy](SECURITY.md); never place tokens,
credentials, personal data or internal deployment details in a public issue.

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
- Standards-based discovery with the provider-suffix fallback used by
  Authentik installations whose issuer contains a path.
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

The current beta includes tested building blocks for:

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

The protocol flow, user provisioning, role reconciliation, session handoff and
auditable dry-run-first migration importer have automated coverage. CiviCRM's
documented generic settings form provides the web administration UI. An
isolated browser-level stack additionally verifies signed OIDC login, JIT
provisioning, managed-role removal, denial and audit integrity against an
official CiviCRM image. Independent security review is still outstanding. The
extension is therefore not ready for production use and must remain disabled
outside isolated evaluation systems.

Existing local accounts are never claimed automatically by matching an email
address. They must be linked explicitly before cutover; see
[Migrating existing users](docs/migration.md).

## Installation

The extension currently targets CiviCRM 6.19 or newer on Standalone and PHP
8.1 through 8.5. It is a beta evaluation release; install and validate it only
in a separate test system. It is not approved for a production rollout.

1. Build or download a release archive whose top-level directory is
   `oidc_sso/`.
2. Extract that directory below a CiviCRM extension directory and make it
   readable by the web-server user. Do not place deployment secrets inside the
   extension directory.
3. Install runtime dependencies in the extension directory:

   ```bash
   composer install --no-dev --classmap-authoritative
   ```

4. Refresh CiviCRM's extension list, install **OpenID Connect SSO**, run the
   pending database upgrades and clear CiviCRM caches. With `cv` this is:

   ```bash
   cv ext:enable oidc_sso
   cv updb -n
   cv flush
   ```

5. Configure CiviCRM credential encryption with a real `CIVICRM_CRED_KEYS`
   key before enabling OIDC. The extension refuses to store browser login
   transactions when CiviCRM is using its plain-text fallback.
6. Create a local break-glass administrator and verify its password before
   enabling OIDC. Keep the local login route separately documented and
   protected.

### Secret files

Generate independent random values for the OIDC client secret and transaction
HMAC key. Store them as owner-controlled, web-server-readable files outside
the web root. Define their paths in `civicrm.settings.php`:

```php
define('CIVICRM_OIDC_SSO_CLIENT_SECRET_FILE', '/run/secrets/oidc_client_secret');
define('CIVICRM_OIDC_SSO_TRANSACTION_KEY_FILE', '/run/secrets/oidc_transaction_key');
define('CIVICRM_OIDC_SSO_AUDIT_KEY_FILE', '/run/secrets/oidc_audit_key');
```

The transaction and audit keys must each contain at least 32 independent
random bytes. A public client
using token-endpoint authentication method `none` does not need a client-secret
file. Container deployments should mount all secret files read-only using their
orchestrator's documented secrets mechanism.

### Audit log

Authentication, identity provisioning/linking, managed-role reconciliation
and migration create extension-owned audit events. They contain only local
numeric user IDs where applicable, controlled codes and numeric counters. OIDC
subjects, tokens, email addresses, usernames, claims and exception messages
are not written to this audit log.

Events are HMAC-chained under a dedicated key. Verify the chain from a fully
bootstrapped CiviCRM CLI with:

```bash
cv scr /path/to/oidc_sso/scripts/verify-audit-log.php
```

The optional local-user number stored with an event is an immutable audit
reference rather than a database foreign key. Deleting a local account
therefore does not rewrite historical events or invalidate their HMAC chain.

The chain is tamper-evident, not immutable. For stronger assurance, retain the
reported head hash periodically in an independently protected system.

## CiviCRM configuration

Register the callback URL before enabling login. For a CiviCRM installation at
`https://crm.example.org`, the exact callback is:

```text
https://crm.example.org/civicrm/oidc/callback
```

Administrators can configure these domain settings at **Administer > System
Settings > OpenID Connect SSO Settings** or through CiviCRM's Settings API.
The page is generated by CiviCRM's documented `setting-admin@1` mixin and
requires either `administer CiviCRM` or the extension-specific
`administer oidc_sso` permission. Secrets deliberately do not appear on the
form; keep them in the protected files described above. The form inherits the
active CiviCRM theme instead of imposing its own light or dark mode. Do not
edit extension tables directly.

| Setting | Example | Purpose |
| --- | --- | --- |
| `oidc_sso_enabled` | `true` | Activates the public login and callback routes. Set this last. |
| `oidc_sso_issuer` | `https://id.example.org/application/o/civicrm/` | Exact issuer advertised by discovery, including any path and trailing slash. |
| `oidc_sso_client_id` | `civicrm` | Client ID assigned by the provider. |
| `oidc_sso_redirect_uri` | `https://crm.example.org/civicrm/oidc/callback` | Must exactly match the registered redirect URI. |
| `oidc_sso_client_authentication` | `client_secret_basic` | `client_secret_basic`, `client_secret_post`, or `none`. Prefer `client_secret_basic` for a confidential web client. |
| `oidc_sso_scopes` | `openid profile email civicrm` | Space-separated scopes; `openid` is mandatory. |
| `oidc_sso_group_claim` | `groups` | ID-token claim containing exact group strings. |
| `oidc_sso_role_mapping` | `{"crm.staff":[3],"crm.admins":[2]}` | Allow list mapping provider groups to existing CiviCRM Standalone role IDs. |

The login start route is `/civicrm/oidc/login`. Unknown provider groups are
ignored, and a user with no mapped role is rejected. Never map arbitrary group
names or create CiviCRM roles automatically from claims.

If the application hostname itself is the intended entry point, configure the
web server or identity-provider application launcher to send `/` to
`/civicrm/oidc/login`. Keep `/civicrm/login` available for the separately
protected break-glass administrator. Do not redirect the local login route
back to OIDC.

Failed OIDC starts and callbacks return to `/civicrm/login` with an error
notice. Keeping that route outside any automatic OIDC redirect prevents a
provider session from repeatedly restarting a failed callback.

`/civicrm/logout` ends the local CiviCRM session. It does not terminate the
OpenID Provider session, so opening the application again can immediately
establish a fresh CiviCRM session through SSO. Deployments which require global
single logout must design and test that separately; this extension does not
currently implement provider logout.

## OpenID Provider configuration: Authentik example

The extension is provider-neutral; Authentik is one tested example. Menu names
may differ between Authentik releases.

1. In Authentik, create an **OAuth2/OpenID Provider** for CiviCRM.
2. Use a confidential client, Authorization Code flow, PKCE method `S256`, and
   an asymmetric signing key with `RS256`. Do not enable implicit or password
   grants.
3. Register only the exact callback URL
   `https://crm.example.org/civicrm/oidc/callback`.
4. Copy the provider's client ID and exact issuer URL into the corresponding
   CiviCRM settings. Put the client secret only in the protected secret file.
5. Attach standard `openid` and `profile` mappings plus deployment-specific
   `email` and `civicrm` scope mappings. Return an email only when it is known
   to be verified, and emit only CiviCRM-relevant groups.
6. Create an Authentik application for the provider and set its launch URL to
   `https://crm.example.org/civicrm/oidc/login`.
7. Bind an Authentik access policy which permits only users intended to reach
   CiviCRM. This outer policy complements rather than replaces CiviCRM's exact
   group-to-role allow list.

A minimal Authentik expression for a dedicated `civicrm` scope can filter the
claim to known groups:

```python
allowed = {"crm.staff", "crm.admins"}
direct_groups = {group.name for group in request.user.ak_groups.all()}
groups = sorted(direct_groups & allowed)
return {"groups": groups}
```

The example intentionally reads direct group memberships. In Authentik,
`request.user.all_groups()` includes inherited groups and may return every
group for a superuser; using it without an explicit hierarchy and superuser
policy can therefore grant unintended application roles. If nested groups are
required, resolve only a reviewed subtree and define separately how Authentik
superusers should map to CiviCRM.

Use a separate `email` scope mapping which returns `email_verified: True` only
when the deployment's identity lifecycle actually guarantees verification.
Do not copy a constant `True` into an environment where email ownership is not
validated. Preview claims for a normal user, an administrator and a denied user
before enabling CiviCRM login.

Authentik installations use an issuer containing the application slug. The
extension first attempts standards-based RFC 8414 discovery and also supports
Authentik's provider-suffix discovery URL, while still requiring the returned
issuer to match exactly.

## Migrating existing CiviCRM users

Migration preserves each existing Standalone user, `UFMatch`, contact, local
password and all records attached to that contact. Never delete and recreate a
user merely to add SSO.

1. Export local user IDs, contact IDs, usernames and email addresses without
   password hashes.
2. Export each Authentik user's immutable OIDC `sub` and the exact issuer.
3. Build and review a one-to-one mapping from `(issuer, sub)` to local CiviCRM
   user ID. Email may help an administrator review a match but must never
   authorize it automatically.
4. Reject duplicate subjects, duplicate local users, missing contacts and any
   conflict with an existing identity link.
5. Use the extension's `ExistingIdentityLinker` to create only the stable
   identity record. It is transactional and refuses to overwrite a link.
6. For each pre-existing CiviCRM role which should henceforth follow an OIDC
   group, explicitly transfer ownership with
   `RoleOwnershipMigrator::claimExisting()`. It refuses roles not currently
   held by the user. Omitted roles remain manual and will not be removed by
   OIDC.
7. Test a pilot normal user, administrator, role grant, role removal, logout,
   re-login and the local break-glass account before expanding access.
8. Keep local passwords during the transition. Retire ordinary local passwords
   only after every account has been verified and a recovery procedure has
   been exercised.

The current alpha includes a CLI importer with a mandatory dry-run-first
workflow. It accepts no email addresses or passwords, does not update contact
profiles and rejects unknown fields. Create a protected JSON manifest outside
the web root:

```json
{
  "issuer": "https://id.example.org/application/o/civicrm/",
  "entries": [
    {
      "subject": "248289761001",
      "user_id": 20,
      "managed_role_ids": [3]
    }
  ]
}
```

Run the importer through a fully bootstrapped CiviCRM CLI. The first command
only validates and reports the proposed links:

```bash
cv scr /path/to/oidc_sso/scripts/migrate-existing-users.php -- \
  --manifest=/protected/path/civicrm-oidc-migration.json
```

Review every `READY` or `UNCHANGED` row. Apply the same validated manifest only
after the dry-run succeeds:

```bash
cv scr /path/to/oidc_sso/scripts/migrate-existing-users.php -- \
  --manifest=/protected/path/civicrm-oidc-migration.json --apply
```

The importer rejects duplicate subjects, duplicate local users, missing users
or contacts, conflicting existing links, and managed roles the user does not
already hold. Application is wrapped in a CiviCRM database transaction. Keep
the manifest readable only by the migration administrator because OIDC
subjects are personal identifiers; archive it according to local policy or
securely remove it after approval and audit retention requirements are met.
Direct inserts into extension tables remain unsupported. A finished web
administration screen is not yet available. See
[Migrating existing users](docs/migration.md) and
[Security model](docs/security.md). The reproducible pre-release lifecycle
matrix is maintained in [Release verification](docs/release-verification.md).

For controlled automation, `--manifest=-` reads the same JSON document from
standard input. Ensure pipeline logs cannot capture the manifest.

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

The [internal pre-release security review](docs/pre-release-security-review.md)
records the current automated evidence, manual findings and unresolved release
gates. It does not replace the independent security review required before a
stable production release.

The initial code targets maintained CiviCRM 6.x releases and PHP versions
supported by those releases. The repository will use Composer for development
tooling and reproducible third-party dependencies.

```bash
docker compose run --rm test
```

The container installs development dependencies and runs Composer validation,
the coding standard and the unit test suite. The same checks run in GitHub
Actions and the Gitea mirror for PHP 8.1 through 8.5.

CI jobs which need a real CiviCRM instance must keep it runner-internal or bind
it only to loopback. Any test instance reachable from another machine requires
identity-provider protection at the reverse proxy, a CiviCRM login/session and
disabled self-registration. Development and test status are never grounds for
anonymous access. See [Security policy](SECURITY.md).

GitHub CI also runs an isolated browser-level stack using the official CiviCRM
image, MariaDB, a pinned mock OpenID Provider and Playwright. It publishes no
host ports, generates a short-lived test CA and secrets for each run, verifies
signed tokens through discovery and JWKS, and destroys its volumes afterward.

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
Development-only CI configuration, local Docker configuration, coding-standard
files and unit tests are excluded through committed `export-ignore` rules.

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
define('CIVICRM_OIDC_SSO_AUDIT_KEY_FILE', '/run/secrets/oidc_audit_key');
```

The transaction and audit keys must each contain at least 32 independent
random bytes. Public clients configured
with token-endpoint authentication method `none` do not use a client-secret
file. The registered redirect URI must exactly match the configured
`oidc_sso_redirect_uri`, normally ending in `/civicrm/oidc/callback`.

Never commit client secrets, private keys, tokens, real user attributes,
deployment hostnames or organization-specific role mappings. Examples and
fixtures must use reserved domains such as `example.org` and synthetic data.

## Publishing and Extension Directory metadata

The repository-root `info.xml` is read by CiviCRM's Extension Manager and the
CiviCRM Extension Directory. It carries the extension key, entrypoint,
maintainer, project URLs, release metadata and CiviCRM/PHP compatibility. CI
validates it, and release archives keep it at `oidc_sso/info.xml` beside the
entrypoint.

Every release tag and manifest version must match. The canonical publication
URLs point to [GitHub](https://github.com/h4g3nGG/civicrm-oidc-sso), while the
original private Gitea repository may remain as a development mirror. Run
`php scripts/validate-manifest.php` after changing release metadata. Beta
releases may be shared with testers, but the official directory only lists
stable releases. After a reviewed stable release exists on public GitHub,
register it on civicrm.org and request approval separately for automated
in-app distribution. The deprecated `downloadUrl` element must not be added;
civicrm.org supplies release download metadata.

## License

Copyright contributors.

Licensed under the GNU Affero General Public License, version 3 or (at your
option) any later version (`AGPL-3.0-or-later`). See [LICENSE](LICENSE).

