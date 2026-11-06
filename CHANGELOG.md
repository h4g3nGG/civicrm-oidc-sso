# Changelog

All notable changes to this project are documented in this file. Versions use
[Semantic Versioning](https://semver.org/) with pre-release identifiers.

## Unreleased

### Added

- Data-minimized audit events for authentication, identity provisioning,
  existing-user migration and managed-role reconciliation.
- Application-serialized HMAC hash chaining and a CLI integrity verifier for
  detecting modification, removal or reordering of audit events.
- A separate file-based audit HMAC key.
- Complete Extension Directory metadata and CI manifest validation.

## 0.2.0-alpha.1 - 2026-11-06

First public evaluation release.

### Added

- OpenID Connect Authorization Code flow with PKCE and browser-bound,
  single-use state and nonce transactions.
- Strict discovery, HTTPS endpoint, token response, ID-token signature and
  registered-claim validation.
- Standards-based discovery plus the provider-suffix discovery layout used by
  Authentik issuers containing a path.
- Stable `(issuer, subject)` identity links and just-in-time Standalone user
  provisioning without CiviCRM core patches.
- Exact allow-list mapping of OIDC group claims to existing CiviCRM roles.
- Tracking and removal of extension-managed roles while preserving manual
  role assignments.
- Explicit ownership transfer for roles on migrated existing users.
- CiviCRM AuthX session handoff after successful authentication and
  authorization.
- Strict existing-user migration manifest with dry-run-first CLI import and an
  explicit `--apply` mode.
- File-based OIDC client and transaction secrets suitable for container secret
  mounts.
- Test matrix for PHP 8.1 through 8.5 and reproducible release archives.

### Security properties

- RSA-signed ID tokens are verified against rotating JWKS keys.
- Issuer, audience, authorized party, expiry, issued-at, not-before, nonce and
  token algorithm are validated.
- Redirects, non-HTTPS provider endpoints, unsigned tokens, replayed callbacks,
  unverified email claims and unmapped authorization groups are rejected.
- Existing users are never linked automatically by email address.

### Known limitations

- Alpha release; no production approval or independent security review yet.
- No finished web administration or migration interface.
- No global OpenID Provider logout; CiviCRM logout ends only the local session.
- No automated full-stack Authentik/CiviCRM browser suite yet.
- Audit events for login, account linking, role changes and migration are not
  yet implemented.
- Migration and provider configuration require an experienced administrator.
