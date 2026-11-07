# Release verification

This checklist supplements the automated unit, style and package jobs. Run it
against an isolated CiviCRM test deployment and a dedicated OpenID Provider
application. Never use production identities or contacts.

## Verified full-stack scenarios

The following scenarios were exercised on 2026-11-07 against CiviCRM 6.19.5:

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
- the audit chain remained valid after the denial and logout scenarios (21
  events, head
  `e5c69f7944545d58a68864b362779386021fa63f3d86a52f65642b44f76bf0ca`);
- all temporary provider users, local users, contacts, identity links, managed
  roles and protected migration manifests were removed after verification.

## Required checks before a beta release

- Confirm that an expired provider session requires fresh provider
  authentication before a new CiviCRM session can be created.
- Verify the audit chain before and after every lifecycle test.
- Obtain an independent security review of protocol validation, account
  linking, role ownership, callback handling and deployment documentation.

## Evidence to retain

Record the tested CiviCRM, PHP, extension and OpenID Provider versions; CI run
ID; test date; expected and observed role IDs; final audit-chain head; and
confirmation that every temporary object was removed. Do not retain passwords,
tokens, raw claims, migration subjects or protected manifests in CI artifacts.
