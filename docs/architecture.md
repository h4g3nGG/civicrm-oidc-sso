# Architecture

## Components

The extension is planned as five independently testable services:

1. **Protocol client** — discovery, Authorization Code Flow with PKCE, token
   exchange, JWKS retrieval and standards-compliant token validation.
2. **Identity linker** — links `(issuer, subject)` to exactly one local user and
   contact. Email and username are mutable attributes, not identity keys.
3. **Provisioner** — creates or updates local records through supported CiviCRM
   APIs and applies an explicit conflict policy.
4. **Role reconciler** — maps allow-listed string claims to existing local role
   identifiers and manages only assignments owned by this extension.
5. **Session bridge** — establishes the browser session through CiviCRM AuthX
   rather than reproducing CiviCRM's session internals.

## Data ownership

The extension owns only its configuration, external identity links, transient
OIDC transactions and the role assignments it records as managed. It must not
remove manually assigned roles or role assignments owned by another system.

OIDC login transactions are addressed in persistence by a purpose-separated
HMAC of `state`; raw state and browser-session identifiers are not persisted.
The repository's consume operation is atomic, so a transaction can be returned
to at most one callback even when requests race. Concrete database access is
isolated behind the repository contract.

## Provisioning model

Provisioning is just-in-time. A valid OIDC login creates or updates the current
user. OIDC is not used to enumerate inactive or never-seen users. Deployments
which require a complete directory mirror need a separate provisioning
protocol such as SCIM or an identity-provider API.

## Compatibility boundary

Production code should use documented CiviCRM APIs, hooks, routes, settings,
cryptography services and AuthX flows. Any unavoidable dependency on an
undocumented implementation detail must be isolated behind an adapter and
covered by compatibility tests before a stable release.

