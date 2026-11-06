<?php

declare(strict_types=1);

use Civi\OidcSso\Civi\SecretFileReader;

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('This verification command is CLI-only.');
}
if (!defined('CIVICRM_OIDC_SSO_AUDIT_KEY_FILE')) {
    throw new RuntimeException('OIDC audit HMAC key file constant is not configured.');
}
$path = constant('CIVICRM_OIDC_SSO_AUDIT_KEY_FILE');
if (!is_string($path)) {
    throw new RuntimeException('OIDC audit HMAC key file constant must be a path string.');
}
$key = (new SecretFileReader())->read($path, 'OIDC audit HMAC key');
$previous = str_repeat('0', 64);
$count = 0;
$dao = CRM_Core_DAO::executeQuery(
    'SELECT event_id, occurred_at, event_type, outcome, uf_match_id, details_json, previous_hash, event_hash'
    . ' FROM civicrm_oidc_sso_audit_event ORDER BY id',
);
while ($dao->fetch()) {
    ++$count;
    if (!hash_equals($previous, (string) $dao->previous_hash)) {
        throw new RuntimeException("Audit chain predecessor mismatch at event {$count}.");
    }
    $canonical = implode("\n", [
        (string) $dao->previous_hash,
        (string) $dao->event_id,
        (string) $dao->occurred_at,
        (string) $dao->event_type,
        (string) $dao->outcome,
        $dao->uf_match_id === null ? '' : (string) $dao->uf_match_id,
        (string) $dao->details_json,
    ]);
    $expected = hash_hmac('sha256', $canonical, $key);
    if (!hash_equals($expected, (string) $dao->event_hash)) {
        throw new RuntimeException("Audit event hash mismatch at event {$count}.");
    }
    $previous = $expected;
}
$dao->free();
$head = CRM_Core_DAO::singleValueQuery('SELECT last_hash FROM civicrm_oidc_sso_audit_head WHERE id = 1');
if ($head !== null && $head !== false && !hash_equals($previous, (string) $head)) {
    throw new RuntimeException('Audit chain head does not match the final event.');
}
printf("AUDIT OK events=%d head=%s\n", $count, $previous);
