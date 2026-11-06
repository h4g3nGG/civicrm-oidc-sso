# Security policy

This project handles authentication and authorization. Until a release is
explicitly marked stable, it must be treated as development software and must
not be deployed in production.

Please report suspected vulnerabilities privately to the repository owner.
Do not open a public issue containing tokens, credentials, personal data,
internal hostnames or exploit details.

Supported releases and a dedicated security contact will be published before
the first stable release.

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

