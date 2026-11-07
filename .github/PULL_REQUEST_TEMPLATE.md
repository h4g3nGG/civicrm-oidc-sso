## Summary

Describe the provider-neutral problem and the proposed change.

## Security impact

Explain effects on authentication, token validation, account linking,
authorization, role ownership, redirects, secrets and audit events. Write
`None` only when none of these boundaries changes.

## Verification

- [ ] Automated tests cover the change.
- [ ] `composer check` passes.
- [ ] `php scripts/validate-manifest.php` passes.
- [ ] No credentials, tokens, personal data, internal hostnames or
      organization-specific mappings are included.
- [ ] Documentation and `CHANGELOG.md` are updated when behavior changes.
- [ ] Only documented CiviCRM extension APIs and OIDC mechanisms are used, or
      any exception is explicitly justified.
