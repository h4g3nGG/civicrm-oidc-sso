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

## Authorization

Authentication does not imply authorization. At least one explicitly mapped
group must be present unless an administrator deliberately configures a
different policy. Unknown groups are ignored and never create local roles.

## Local recovery

A local break-glass administrator may remain available through a separately
controlled route. Its use must be logged, rate-limited and excluded from OIDC
role reconciliation.

