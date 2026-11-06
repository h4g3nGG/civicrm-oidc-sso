<?php

declare(strict_types=1);

namespace Civi\OidcSso\Audit;

final class AuditEvent
{
    public const AUTHENTICATION = 'authentication';
    public const IDENTITY_CREATED = 'identity_created';
    public const IDENTITY_LINKED = 'identity_linked';
    public const IDENTITY_UPDATED = 'identity_updated';
    public const ROLE_RECONCILIATION = 'role_reconciliation';
    public const MIGRATION = 'migration';

    public const SUCCESS = 'success';
    public const REJECTED = 'rejected';
    public const DRY_RUN = 'dry_run';
    public const APPLIED = 'applied';
}
