<?php

declare(strict_types=1);

use CRM_OidcSso_ExtensionUtil as E;

return [
    'name' => 'OidcSsoLoginTransaction',
    'table' => 'civicrm_oidc_sso_transaction',
    'getInfo' => fn(): array => [
        'title' => E::ts('OIDC Login Transaction'),
        'title_plural' => E::ts('OIDC Login Transactions'),
        'description' => E::ts('Short-lived, encrypted OpenID Connect login state.'),
        'log' => false,
    ],
    'getIndices' => fn(): array => [
        'UI_state_key' => [
            'fields' => ['state_key' => true],
            'unique' => true,
        ],
        'index_expires_at' => [
            'fields' => ['expires_at' => true],
        ],
    ],
    'getFields' => fn(): array => [
        'id' => [
            'title' => E::ts('ID'),
            'sql_type' => 'int unsigned',
            'input_type' => 'Number',
            'required' => true,
            'primary_key' => true,
            'auto_increment' => true,
        ],
        'state_key' => [
            'title' => E::ts('State Key'),
            'sql_type' => 'char(64)',
            'input_type' => 'Text',
            'required' => true,
        ],
        'payload' => [
            'title' => E::ts('Encrypted Payload'),
            'sql_type' => 'text',
            'input_type' => 'TextArea',
            'required' => true,
        ],
        'expires_at' => [
            'title' => E::ts('Expiry Time'),
            'sql_type' => 'int unsigned',
            'input_type' => 'Number',
            'required' => true,
        ],
    ],
];
