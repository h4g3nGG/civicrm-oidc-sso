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

| Version | Security support | Production status |
| --- | --- | --- |
| `main` / `0.3.0-beta.2` | Best-effort beta fixes | Not approved |
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

