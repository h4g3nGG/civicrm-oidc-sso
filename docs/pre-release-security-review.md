# Internal pre-release security review

This document records the maintainer's internal review through commit
`2673dee` and release `0.3.0-beta.2` on 2026-11-07. It is evidence for release
preparation, not an independent security assessment and not production
approval.

## Scope

The review covered the OpenID Connect authorization-code flow, PKCE and login
transaction storage, discovery and JWKS processing, ID-token verification,
account provisioning and linking, role reconciliation, CiviCRM AuthX handoff,
secret handling, audit integrity, packaging and public-repository hygiene.

## Automated evidence

- `composer validate --strict` passed.
- `composer audit --locked` reported no known security advisories.
- Direct dependency review found only new major versions of Guzzle, PHPUnit
  and PHP_CodeSniffer; no compatible security or maintenance update was
  pending.
- PHP_CodeSniffer passed for all 108 scanned files.
- PHPUnit 10.5.66 passed 126 tests with 276 assertions on PHP 8.5.12.
- `scripts/validate-manifest.php` passed.
- `scripts/build-release.sh` produced a release archive containing the locked
  production dependencies.
- Release-archive validation rejected the superseded beta.1 archive's macOS
  extended attributes and accepted the platform-neutral beta.2 archive. The
  published beta.2 asset has SHA-256
  `ef444b95a2825f9dd8caf4e088deb46540c91a038ef72e1cb0a75cd4022fcb78`.
- The tracked tree and Git history were searched for private keys, bearer
  tokens, credentials, machine-local paths and deployment hostnames. No
  credential or private-key material was found.

GitHub Actions run `42368536079` passed the PHP 8.1, 8.2, 8.3, 8.4 and 8.5
jobs plus the package job. A fresh CI run is required for every subsequent
release candidate.

## Manual findings

- Login state is random, HMAC-indexed, encrypted at rest, browser-bound,
  single-use and time-limited.
- Authorization Code Flow uses PKCE S256 and an exact redirect URI.
- Discovery requires HTTPS and exact issuer equality. Provider metadata must
  advertise Authorization Code Flow, PKCE S256 and an allowed signing
  algorithm.
- ID tokens require a known `kid`, an allowed asymmetric algorithm and a
  valid signature. Issuer, audience, authorized party, expiry, issued-at,
  not-before and nonce are validated.
- Post-login return paths are restricted to local absolute paths.
- Existing local accounts are linked only through an explicit migration
  manifest, never by an email match during login.
- Provider groups map through an exact administrator-defined allow list.
  Extension-owned roles are tracked separately from manual role assignments.
- Client, transaction and audit secrets are loaded from protected files.
- Failed callbacks terminate on the local login page and do not automatically
  restart OIDC.

## Open release gates

The following items must be completed before the project is described as a
production-ready stable release:

1. Obtain an independent review of protocol validation, endpoint trust and
   SSRF assumptions, account linking, role ownership, callback behavior,
   CiviCRM AuthX handoff and deployment guidance.
2. Resolve every material review finding and repeat the full verification
   matrix.
3. Publish a dedicated private security contact and a supported-version
   policy.
4. Add an automated isolated browser-level OIDC/CiviCRM test. The existing
   manual full-stack evidence remains required until that test exists.

The publication metadata now points to the maintainer-owned GitHub repository.
This accurately represents the project's current ownership and makes no claim
of affiliation with the CiviCRM project or another organization.

The configured issuer and its discovered HTTPS endpoints form a trust
boundary. Cross-origin provider endpoints are permitted by OpenID Connect and
are therefore not rejected solely for using another host. An independent
review must explicitly assess this choice, the deployment's outbound-network
policy and the consequences of a compromised discovery document.

## Release decision

The current result supports continued test deployment and preparation of a
beta candidate. It does not satisfy the independent-review gate and must not
be represented as production approval.
