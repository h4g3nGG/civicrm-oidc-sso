# OpenID Connect SSO for CiviCRM

`civicrm-oidc-sso` is a provider-neutral CiviCRM extension for secure browser
single sign-on using OpenID Connect (OIDC). The project aims to support
just-in-time user provisioning and deterministic mapping of trusted OIDC group
claims to existing CiviCRM roles without modifying CiviCRM core.

> **Development status:** design and implementation in progress. Do not use in
> production yet.

## Goals

- Authorization Code Flow with PKCE.
- Complete validation of ID tokens, including signature, issuer, audience,
  authorized party, expiry, not-before time and nonce.
- CSRF and login-injection protection through one-time `state` and `nonce`.
- Provider-neutral discovery and JWKS support.
- Just-in-time creation and update of CiviCRM users and contacts.
- Stable account linking through the tuple `(issuer, subject)` rather than an
  email address or mutable username.
- Allow-list-based mapping of OIDC groups to pre-existing CiviCRM roles.
- Removal of extension-managed roles when claims change.
- A separately controlled local break-glass login.
- No core patches and no changes to the identity-provider software.
- Auditable configuration, login and role-mapping events.

## Non-goals

- Mirroring every identity-provider user before their first login. OIDC
  describes authentication and claims for the current user; it is not a user
  directory synchronization protocol.
- Automatically creating roles from arbitrary group names received from an
  identity provider.
- Treating an email address as a permanent identity key.
- Replacing the identity provider's access policies or multi-factor
  authentication.

## Planned login flow

1. CiviCRM redirects an unauthenticated browser to the configured OpenID
   Provider using Authorization Code Flow with PKCE.
2. The callback validates the authorization response and the ID token.
3. The extension links or provisions the local user through supported CiviCRM
   APIs.
4. Explicit group-to-role mappings are reconciled.
5. A CiviCRM browser session is established through the documented AuthX
   session flow.

See [Architecture](docs/architecture.md) and [Security model](docs/security.md).

## Configuration model (planned)

Configuration will be managed through CiviCRM settings and an administration
form. Secrets must not be committed to this repository. At minimum, an
installation will configure:

- issuer/discovery URL;
- client ID and client authentication method;
- client secret or private-key reference;
- exact redirect URI;
- requested scopes (`openid`, `profile`, `email`, and a group claim scope);
- claim names for username, display name, email and groups;
- an explicit group-to-role allow list;
- provisioning and deprovisioning policies;
- local break-glass login policy.

## Related projects

- [CiviCRM Core on GitHub](https://github.com/civicrm/civicrm-core)
- [CiviCRM developer documentation](https://docs.civicrm.org/dev/en/latest/)
- [CiviCRM AuthX documentation](https://docs.civicrm.org/dev/en/latest/framework/authx/)
- [CiviCRM OAuth Login (`oauth_login`)](https://lab.civicrm.org/extensions/oauth_login)

The `oauth_login` extension is an important prior experiment and design
reference. This project is a separate implementation with an explicit security
model, complete cryptographic token validation, provider-neutral group claims,
role reconciliation and production-focused tests. No claim is made that the
projects are affiliated.

## Development

The initial code targets maintained CiviCRM 6.x releases and PHP versions
supported by those releases. The repository will use Composer for development
tooling and reproducible third-party dependencies.

```bash
composer install
composer test
```

Never commit client secrets, private keys, tokens, real user attributes,
deployment hostnames or organization-specific role mappings. Examples and
fixtures must use reserved domains such as `example.org` and synthetic data.

## License

Copyright contributors.

Licensed under the GNU Affero General Public License, version 3 or (at your
option) any later version (`AGPL-3.0-or-later`). See [LICENSE](LICENSE).

