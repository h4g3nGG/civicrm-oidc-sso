<?php

declare(strict_types=1);

return [
    'name' => 'OidcSsoAuditEvent',
    'table' => 'civicrm_oidc_sso_audit_event',
    'getInfo' => fn(): array => ['title' => 'OIDC Audit Event', 'title_plural' => 'OIDC Audit Events', 'log' => false],
    'getIndices' => fn(): array => [
        'UI_event_id' => ['fields' => ['event_id' => true], 'unique' => true],
        'index_occurred_at' => ['fields' => ['occurred_at' => true]],
    ],
    'getFields' => fn(): array => [
        'id' => ['title' => 'ID', 'sql_type' => 'int unsigned', 'input_type' => 'Number', 'required' => true, 'primary_key' => true, 'auto_increment' => true],
        'event_id' => ['title' => 'Event ID', 'sql_type' => 'char(32)', 'input_type' => 'Text', 'required' => true],
        'occurred_at' => ['title' => 'Occurred At', 'sql_type' => 'datetime', 'input_type' => 'Select Date', 'required' => true],
        'event_type' => ['title' => 'Event Type', 'sql_type' => 'varchar(64)', 'input_type' => 'Text', 'required' => true],
        'outcome' => ['title' => 'Outcome', 'sql_type' => 'varchar(64)', 'input_type' => 'Text', 'required' => true],
        'uf_match_id' => ['title' => 'User Framework Match', 'sql_type' => 'int unsigned', 'input_type' => 'EntityRef', 'required' => false, 'entity_reference' => ['entity' => 'UFMatch', 'key' => 'id', 'on_delete' => 'SET NULL']],
        'details_json' => ['title' => 'Details', 'sql_type' => 'varchar(1024)', 'input_type' => 'Text', 'required' => true],
        'previous_hash' => ['title' => 'Previous Hash', 'sql_type' => 'char(64)', 'input_type' => 'Text', 'required' => true],
        'event_hash' => ['title' => 'Event Hash', 'sql_type' => 'char(64)', 'input_type' => 'Text', 'required' => true],
    ],
];
