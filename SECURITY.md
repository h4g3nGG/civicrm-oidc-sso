# Security policy

This project handles authentication and authorization. Until a release is
explicitly marked stable, it must be treated as development software and must
not be deployed in production.

Please report suspected vulnerabilities privately to
`civicrm-oidc-sso@whatchamacallit.xyz`. Include the affected version, a concise
impact assessment and safe reproduction steps. Do not send live credentials,
tokens, personal data or unredacted production logs. Do not open a public issue
containing tokens, credentials, personal data, internal hostnames or exploit
details.

Receipt will be acknowledged when reasonably possible, but this volunteer
pre-release project does not currently promise a response or remediation SLA.
Supported stable release lines will be listed here before the first stable
release.

Security support applies only to CiviCRM Standalone. Reports involving Drupal,
WordPress, Joomla or Backdrop integrations are welcome as information but are
outside the supported 1.0 compatibility boundary.

## Independent review requested

The implementation is feature complete for its intended CiviCRM Standalone
scope, but it has not yet received an independent security assessment. It must
not be used in production until the protocol implementation, account linking,
role ownership and deployment guidance have been independently reviewed.

Qualified reviewers are invited to use the
[public security-review scope](docs/security-review-scope.md). Public issues
may be used to coordinate a review, funding or non-sensitive scope questions.
Suspected or confirmed vulnerabilities, exploit details and sensitive evidence
must be sent privately to the security address above.

| Version | Security support | Production status |
| --- | --- | --- |
| `main` / `1.0.0-rc.1` candidate | Best-effort release-candidate fixes | Not approved |
| `0.3.0-beta.6` candidate | Unpublished and superseded | Not approved |
| `0.3.0-beta.4` | Superseded evaluation build | Not approved |
| `0.3.0-beta.3` | Superseded evaluation build | Not approved |
| `0.3.0-beta.2` | Superseded evaluation build | Not approved |
| `0.2.0-alpha.1` | Superseded evaluation build | Not approved |

## Test and CI deployments

Development status never permits anonymous application access or public
self-registration. Every persistent test host must be protected by an
identity-provider access policy at the reverse proxy and must still require a
valid local CiviCRM session after the edge check. Local break-glass login is an
administrator recovery path, not anonymous access.

Ephemeral CI/SMOKE CiviCRM instances must be isolated on a runner-only Docker
network or bound exclusively to loopback. If an instance is reachable beyond
its runner, it must use the same identity-provider edge protection as a
persistent test host. Public bind addresses and public DNS are prohibited for
unprotected CI instances. CiviCRM and identity-provider enrollment/self-signup
flows must remain disabled in every environment.

