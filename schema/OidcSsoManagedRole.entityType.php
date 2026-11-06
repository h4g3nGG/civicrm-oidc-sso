<?php

declare(strict_types=1);

use CRM_OidcSso_ExtensionUtil as E;

return [
    'name' => 'OidcSsoManagedRole',
    'table' => 'civicrm_oidc_sso_managed_role',
    'getInfo' => fn(): array => [
        'title' => E::ts('OIDC Managed Role'),
        'title_plural' => E::ts('OIDC Managed Roles'),
        'description' => E::ts('Local role assignments added and owned by the OIDC extension.'),
        'log' => true,
    ],
    'getIndices' => fn(): array => [
        'UI_user_role' => [
            'fields' => ['uf_match_id' => true, 'role_id' => true],
            'unique' => true,
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
        'uf_match_id' => [
            'title' => E::ts('User Framework Match'),
            'sql_type' => 'int unsigned',
            'input_type' => 'EntityRef',
            'required' => true,
            'entity_reference' => [
                'entity' => 'UFMatch',
                'key' => 'id',
                'on_delete' => 'CASCADE',
            ],
        ],
        'role_id' => [
            'title' => E::ts('Standalone Role ID'),
            'sql_type' => 'int unsigned',
            'input_type' => 'Number',
            'required' => true,
        ],
    ],
];
