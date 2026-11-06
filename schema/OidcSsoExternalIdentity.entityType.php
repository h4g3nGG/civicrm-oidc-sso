<?php

declare(strict_types=1);

use CRM_OidcSso_ExtensionUtil as E;

return [
    'name' => 'OidcSsoExternalIdentity',
    'table' => 'civicrm_oidc_sso_identity',
    'getInfo' => fn(): array => [
        'title' => E::ts('OIDC External Identity'),
        'title_plural' => E::ts('OIDC External Identities'),
        'description' => E::ts('Stable link between an OIDC subject and a local CiviCRM account.'),
        'log' => true,
    ],
    'getIndices' => fn(): array => [
        'UI_issuer_subject' => [
            'fields' => ['issuer_hash' => true, 'subject_hash' => true],
            'unique' => true,
        ],
        'UI_uf_match' => [
            'fields' => ['uf_match_id' => true],
            'unique' => true,
        ],
        'index_contact' => [
            'fields' => ['contact_id' => true],
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
        'issuer_hash' => [
            'title' => E::ts('Issuer Hash'),
            'sql_type' => 'char(64)',
            'input_type' => 'Text',
            'required' => true,
        ],
        'subject_hash' => [
            'title' => E::ts('Subject Hash'),
            'sql_type' => 'char(64)',
            'input_type' => 'Text',
            'required' => true,
        ],
        'issuer' => [
            'title' => E::ts('Issuer'),
            'sql_type' => 'varchar(1024)',
            'input_type' => 'Url',
            'required' => true,
        ],
        'subject' => [
            'title' => E::ts('Subject'),
            'sql_type' => 'varchar(255)',
            'input_type' => 'Text',
            'required' => true,
        ],
        'contact_id' => [
            'title' => E::ts('Contact'),
            'sql_type' => 'int unsigned',
            'input_type' => 'EntityRef',
            'required' => true,
            'entity_reference' => [
                'entity' => 'Contact',
                'key' => 'id',
                'on_delete' => 'CASCADE',
            ],
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
    ],
];
