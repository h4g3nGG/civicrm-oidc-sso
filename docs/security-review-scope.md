# Independent security-review scope

## Status and objective

The implementation is feature complete for its intended CiviCRM Standalone
scope. It is not approved for production use because it has not received an
independent security assessment. This document gives qualified reviewers a
bounded starting point for reviewing the candidate before a stable 1.0
release.

Coordinate non-sensitive review questions publicly through the repository's
security-review issue template. Send suspected vulnerabilities, exploit
details and sensitive evidence privately according to [SECURITY.md](../SECURITY.md).

## Supported target

- CiviCRM Standalone 6.19 or newer.
- PHP 8.1 through 8.5.
- OpenID Connect Authorization Code Flow with PKCE S256.
- Provider-neutral discovery and JWKS processing, with Authentik as the primary
  real-provider integration test.

Drupal, WordPress, Joomla and Backdrop integrations are outside the version 1.0
scope. Provider-wide logout, directory-wide user synchronization and automatic
creation of CiviCRM roles from provider claims are also out of scope.

## Review areas

The assessment should include manual source review and dynamic testing of:

1. Authorization requests, exact redirect handling, browser binding, one-time
   `state`, one-time `nonce` and PKCE S256.
2. Discovery and JWKS trust boundaries, HTTPS enforcement, redirect rejection,
   response-size limits, issuer equality, key selection and key rotation.
3. Authorization-code exchange and ID-token validation, including signature,
   algorithm allow lists, `kid`, `iss`, `aud`, `azp`, `exp`, `iat` and `nbf`.
4. Replay, login injection, session fixation, open redirect, algorithm
   confusion, malformed JWT/JWK and concurrent-callback scenarios.
5. Stable `(issuer, subject)` identity linking, explicit existing-user
   migration, collision handling and verified-email requirements for new
   users.
6. Allow-listed group-to-role mapping, managed-role removal, preservation of
   manual roles and denial when no authorized role remains.
7. CiviCRM AuthX session handoff, local break-glass access and behavior when
   the provider, discovery endpoint or JWKS endpoint is unavailable.
8. Secret-file handling, CiviCRM credential encryption, audit-data
   minimization, HMAC audit-chain integrity and sensitive logging behavior.
9. Installation, configuration, upgrades, disable/uninstall behavior and the
   accuracy of operational and migration guidance.
10. Dependencies, release packaging and repository supply-chain controls.

The implementation should be assessed against OpenID Connect Core 1.0,
applicable OAuth 2.0 security best current practices, PKCE, JWT/JWK
requirements and relevant OWASP ASVS authentication, session-management and
cryptography controls.

## Evidence available

- Unit tests for protocol, identity, authorization, persistence and migration
  behavior.
- PHP 8.1 through 8.5 CI jobs.
- An isolated browser stack with a signed mock OpenID Provider.
- A separate isolated browser stack using an official Authentik image.
- Reproducible migration, role-removal and audit-chain verification scenarios.
- The internal review record in
  [pre-release-security-review.md](pre-release-security-review.md) and the
  lifecycle matrix in [release-verification.md](release-verification.md).

Internal tests and the maintainer's review are evidence for the assessment;
they are not substitutes for reviewer independence.

## Requested deliverables

The preferred engagement produces:

- a written report describing scope, tested commit and methodology;
- findings with severity, impact, reproduction guidance and remediation;
- a clear list of untested assumptions and residual risks;
- a retest of material fixes; and
- a publishable final or redacted report after coordinated remediation.

No reviewer should test a production deployment or use real identities,
contacts, credentials or claims. Testing must use the isolated fixtures or a
dedicated non-production environment.
