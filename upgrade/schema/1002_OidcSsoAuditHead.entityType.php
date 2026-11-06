<?php

declare(strict_types=1);

return [
    'name' => 'OidcSsoAuditHead',
    'table' => 'civicrm_oidc_sso_audit_head',
    'getInfo' => fn(): array => ['title' => 'OIDC Audit Head', 'title_plural' => 'OIDC Audit Heads', 'log' => false],
    'getFields' => fn(): array => [
        'id' => ['title' => 'ID', 'sql_type' => 'int unsigned', 'input_type' => 'Number', 'required' => true, 'primary_key' => true],
        'last_hash' => ['title' => 'Last Hash', 'sql_type' => 'char(64)', 'input_type' => 'Text', 'required' => true],
    ],
];
