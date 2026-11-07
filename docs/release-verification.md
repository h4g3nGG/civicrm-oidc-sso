# Release verification

This checklist supplements the automated unit, style and package jobs. Run it
against an isolated CiviCRM test deployment and a dedicated OpenID Provider
application. Never use production identities or contacts.

## Verified full-stack scenarios

The following scenarios were exercised on 2026-11-07 against CiviCRM 6.19.5:

- merged `main` CI run `42374151310` built an isolated Docker network with no
  published host ports and completed the browser-level signed OIDC flow;
- the automated flow provisioned one authorized user exactly once, assigned
  the configured role, removed that extension-managed role after the same
  subject returned without a mapped group, and denied a distinct unmapped
  subject;
- the automated postconditions found no duplicate user or contact and
  verified the final HMAC audit chain;
- merged `main` CI run `42387362860` repeated the complete matrix and passed a
  separate real-Authentik 2026.8.5 browser flow covering password login,
  provider-suffix discovery, Authorization Code with PKCE S256, signed-token
  validation, a verified test-owned email claim, group mapping, JIT
  provisioning, role assignment and final audit verification;
- Gitea stability runs `262`, `263` and `265` independently repeated the mock
  full-stack browser flow on three self-hosted runners after hardening the
  page-readiness assertions. All three runs passed the browser flow, user and
  role postconditions, existing-user migration checks and final HMAC audit
  verification. Runs `260` and `264` were excluded as product evidence because
  their Playwright image extraction stopped with an explicitly diagnosed
  runner-storage exhaustion before any browser assertion ran;

- the exact published `0.3.0-beta.2` archive was checksum-verified, validated,
  installed and discovered as version `0.3.0-beta.2`; no extension database
  upgrade was pending, both application containers remained healthy and the
  protected login entry point redirected to the configured OpenID Provider;
- the beta.3 candidate registered the standard
  `civicrm/admin/setting/oidc_sso` route through `CRM_Admin_Form_Generic` and
  exposed the extension-specific `administer oidc_sso` permission through
  APIv4 without placing secret values on the form;
- the beta.4 candidate preserved the existing group-to-role mapping while
  converting its Settings API array value to canonical JSON, rendered the
  settings form without a PHP type error, and retained readable contrast in
  CiviCRM's `default`/Greenwich theme and a dark third-party theme;

- a new OIDC subject completed provider password and MFA authentication, was
  provisioned once and reached the CiviCRM home page;
- an allow-listed staff group assigned only the configured staff role;
- adding an administrator group added the configured administrator role, and
  removing that group removed the role again after a fresh OIDC login;
- account deletion left the serialized HMAC audit chain valid;
- migration dry-run wrote no identity or managed-role records;
- migration apply linked an existing local user without changing its user ID,
  contact ID, username, email, profile or existing roles;
- migration transferred ownership only for the explicitly approved role;
- a later unmapped claim was rejected, removed the extension-managed role and
  preserved the independently assigned manual role;
- an active provider user without any mapped CiviCRM group was denied by the
  provider application policy and no CiviCRM user, email or session was
  created;
- after that provider user was disabled and all provider sessions were
  revoked, a fresh login was rejected before CiviCRM was reached;
- local logout ended the CiviCRM session but deliberately left the provider
  session intact; revisiting the protected application therefore completed a
  fresh OIDC login without another password or MFA prompt, confirming the
  documented absence of provider-wide single logout;
- after the provider session was revoked, a locally logged-out browser was
  returned to the provider login form and could not create a new CiviCRM
  session without fresh provider authentication;
- an intentionally invalid callback terminated on `/civicrm/login` instead of
  restarting OIDC, preventing a provider-session redirect loop;
- the audit chain remained valid after the denial, logout and provider-session
  expiry scenarios (44
  events, head
  `dedf2b111f254fc6d66d9def1208be356a36e45f14ccef8aeab9e6b358043cd9`);
- all temporary provider users, local users, contacts, identity links, managed
  roles and protected migration manifests were removed after verification.

## Required checks before every prerelease or stable release

- Verify the audit chain before and after every lifecycle test.
- Obtain an independent security review of protocol validation, account
  linking, role ownership, callback handling and deployment documentation.

## Evidence to retain

Record the tested CiviCRM, PHP, extension and OpenID Provider versions; CI run
ID; test date; expected and observed role IDs; final audit-chain head; and
confirmation that every temporary object was removed. Do not retain passwords,
tokens, raw claims, migration subjects or protected manifests in CI artifacts.
