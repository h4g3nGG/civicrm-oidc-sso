# Changelog

All notable changes to this project are documented in this file. Versions use
[Semantic Versioning](https://semver.org/) with pre-release identifiers.

## Unreleased

### Documentation

- Align the migration, CI and verification documentation with the published
  `0.3.0-beta.2` evaluation release.

## 0.3.0-beta.2 - 2026-11-07

Packaging correction for the public beta. Use this release instead of
`0.3.0-beta.1`.

### Fixed

- Suppress macOS extended attributes while building release archives and
  reject AppleDouble, Finder metadata, path traversal, out-of-tree paths and
  symbolic links during package validation.

## 0.3.0-beta.1 - 2026-11-07

Public beta for isolated evaluation environments. This release is not
approved for production use and has not completed an independent security
review.

### Added

- Data-minimized audit events for authentication, identity provisioning,
  existing-user migration and managed-role reconciliation.
- Application-serialized HMAC hash chaining and a CLI integrity verifier for
  detecting modification, removal or reordering of audit events.
- A separate file-based audit HMAC key.
- Complete Extension Directory metadata and CI manifest validation.
- A documented full-stack release-verification matrix covering JIT
  provisioning, role changes, existing-user migration, denial and cleanup.
- An internal pre-release security-review record with automated evidence,
  manual findings and explicit remaining release gates.
- A GitHub Actions matrix and canonical GitHub publication links while
  retaining the original Gitea repository as a development mirror.
- Public security and support contacts, contribution guidance, issue forms,
  a pull-request checklist and a project code of conduct.

### Fixed

- Keep the audit log's local-user reference immutable when the corresponding
  CiviCRM account is deleted, so account lifecycle cleanup cannot invalidate
  the HMAC chain.
- End failed login starts and callbacks on the local login page instead of
  allowing an existing provider session to restart them in a redirect loop.

### Known limitations

- No independent security review or production approval yet.
- No finished web administration or migration interface.
- No automated full-stack browser suite; the documented isolated full-stack
  verification remains manual.
- No OpenID Provider-wide logout; CiviCRM logout ends only the local session.
- Configuration and existing-user migration require an experienced
  administrator.

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
