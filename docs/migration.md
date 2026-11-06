# Migrating existing users

An OIDC migration must preserve the existing CiviCRM user, `UFMatch`, contact,
roles and all records attached to that contact. The extension therefore adds a
stable external-identity link instead of replacing or recreating local records.

## Safe migration sequence

1. Export the local CiviCRM user ID, linked contact ID, username and email for
   review. Do not export password hashes.
2. Export the OpenID Provider's immutable issuer and subject identifiers.
3. Build an administrator-reviewed mapping from each OIDC subject to exactly
   one existing CiviCRM user ID. Matching email addresses may be shown as a
   suggestion but must not authorize a link.
4. Run a dry check for duplicate subjects, duplicate local users, missing
   contacts and conflicts with links already stored by the extension.
5. Create only the extension-owned identity-link records. Do not change the
   contact or user during this import.
6. For every existing role which should subsequently follow an OIDC group,
   explicitly transfer ownership of that already assigned role to the
   extension. The migration must refuse roles which the user does not currently
   hold. Roles omitted from this step remain manual and are never removed by
   OIDC reconciliation.
7. Test OIDC login with a small pilot group. Once linked, later successful
   logins may update explicitly permitted mutable profile fields.
8. Keep local passwords during a transition period. Disable ordinary local
   login only after all users have been verified, while retaining a separately
   controlled break-glass administrator.

The linking operation is transactional and refuses to overwrite an existing
`(issuer, subject)` link. The database also enforces that a local `UFMatch` can
belong to at most one external identity. Ambiguous rows require an explicit
administrator decision.

Role ownership is deliberately separate from identity linking. This prevents
an initial OIDC login from silently taking control of a manually assigned role.
`RoleOwnershipMigrator::claimExisting()` validates that every requested role is
already assigned, preserves existing managed assignments and records only the
administrator-approved ownership transfer.

## Reviewed CLI import

`scripts/migrate-existing-users.php` consumes a strict JSON manifest containing
one exact issuer and a list of `subject`, `user_id`, and optional
`managed_role_ids` values. Run it with `cv scr`; without `--apply` it performs
validation and reports the complete plan without writing. An apply run repeats
all checks and wraps the links and role-ownership transfers in a CiviCRM
transaction.

The manifest must be protected as identity data. It must never contain local
passwords, password hashes, OIDC client secrets, access tokens or refresh
tokens. Direct database inserts are not a supported migration method.

## New users

If no stable link exists, just-in-time provisioning is allowed only when the
OIDC email is verified and the username and email do not collide with any
existing local account or contact. A collision stops login and requests an
administrator-managed migration link; it never silently creates a duplicate or
claims an existing profile.
