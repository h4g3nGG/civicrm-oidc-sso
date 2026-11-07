# Security model

## Trust boundaries

- The configured issuer and its discovered endpoints are trusted only after
  exact issuer matching and HTTPS validation.
- Claims are trusted only from an ID token whose signature and registered
  claims have been validated.
- Group names have no authority unless present in the administrator-defined
  allow list.
- The browser is untrusted. OIDC transaction state is one-time, bound to the
  initiating session and expires quickly.

## Mandatory validation

The implementation must validate at least:

- TLS certificates and host names;
- discovery issuer equality;
- permitted signing algorithm and signature using the issuer's JWKS;
- `iss`, `aud`, and `azp` where applicable;
- `exp`, `iat`, and `nbf` with a small configurable clock skew;
- exact, one-time `nonce`;
- exact, one-time `state`;
- PKCE verifier and `S256` challenge;
- callback URL and post-login return URL allow lists.

The implementation must reject unsigned tokens, algorithm confusion, unknown
critical headers, missing mandatory claims, duplicate external identity links,
unverified account-link requests and open redirects.

Login state is bound to the initiating browser session and removed atomically
before callback validation continues. Expired, wrongly bound and failed
transactions remain consumed, preventing replay after any callback attempt.
Nonce, PKCE verifier, return path and browser binding are encrypted at rest
with CiviCRM credential encryption. The extension refuses transaction writes
when the host falls back to plain-text credential storage.

## Authorization

Authentication does not imply authorization. At least one explicitly mapped
group must be present unless an administrator deliberately configures a
different policy. Unknown groups are ignored and never create local roles.
The extension records only roles which it actually added. On later logins it
may remove those managed assignments, but it never removes a role which was
already present manually. A login with no explicitly mapped role is rejected
after any obsolete extension-managed roles have been removed.

After authorization, CiviCRM's own JWT service creates a 60-second AuthX token
with a random `jti`. The documented AuthX auto-session flow consumes it to
establish the browser session; the extension does not manipulate session
cookies or CiviCRM session internals.

## Local recovery

A local break-glass administrator may remain available through a separately
controlled route. Its use must be logged, rate-limited and excluded from OIDC
role reconciliation.

## Audit trail

The extension records controlled events for authentication, identity
provisioning/linking, role reconciliation and migration. Its detail schema is
an explicit allow list: local numeric user IDs, counters and short enumerated
reason codes only. It excludes tokens, claims, issuer/subject identifiers,
email addresses, usernames and exception messages.

Audit events form an HMAC-SHA-256 chain. A transactional singleton head row is
locked while appending an event, providing deterministic order under
concurrent logins. The separate audit key is read from a protected file and
must not be stored with the database. The CLI verifier detects modification,
deletion and reordering when the attacker does not possess that key.

The optional local-user number is stored as an immutable audit reference, not
as a foreign key. Local account deletion cannot rewrite that historical value
and consequently cannot invalidate the HMAC chain.

This is application-level tamper evidence, not immutable or externally
anchored logging. An attacker with both database write access and the audit key
can rebuild the chain. Deployments needing stronger evidence should export
events and periodically anchor the verifier's final head hash in independently
protected storage.

