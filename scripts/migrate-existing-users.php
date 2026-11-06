<?php

declare(strict_types=1);

use Civi\OidcSso\Authorization\RoleOwnershipMigrator;
use Civi\OidcSso\Civi\CiviApi4Client;
use Civi\OidcSso\Civi\CiviExternalIdentityRepository;
use Civi\OidcSso\Civi\CiviManagedRoleRepository;
use Civi\OidcSso\Civi\CiviProvisioningTransaction;
use Civi\OidcSso\Civi\CiviStandaloneAccountGateway;
use Civi\OidcSso\Civi\CiviStandaloneRoleGateway;
use Civi\OidcSso\Identity\ExistingIdentityLinker;
use Civi\OidcSso\Migration\MigrationManifest;

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('This migration command is CLI-only.');
}

$manifestPath = null;
$apply = false;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--apply') {
        $apply = true;
        continue;
    }
    if (str_starts_with($argument, '--manifest=')) {
        $manifestPath = substr($argument, strlen('--manifest='));
        continue;
    }
    if ($argument === '--') {
        continue;
    }
    throw new InvalidArgumentException("Unknown argument: {$argument}");
}
if (!is_string($manifestPath) || $manifestPath === '') {
    throw new InvalidArgumentException('Use --manifest=/absolute/path/to/manifest.json or --manifest=- for stdin.');
}
$json = file_get_contents($manifestPath === '-' ? 'php://stdin' : $manifestPath);
if ($json === false) {
    throw new RuntimeException('Migration manifest cannot be read.');
}
$manifest = MigrationManifest::fromJson($json);

$api = new CiviApi4Client();
$accounts = new CiviStandaloneAccountGateway($api);
$identities = new CiviExternalIdentityRepository();
$roles = new CiviStandaloneRoleGateway($api);
$managed = new CiviManagedRoleRepository();
$transaction = new CiviProvisioningTransaction();
$linker = new ExistingIdentityLinker($identities, $accounts, $transaction);
$roleMigrator = new RoleOwnershipMigrator($roles, $managed);

$prepared = [];
foreach ($manifest->entries as $index => $entry) {
    $account = $accounts->byUserId($entry->userId);
    $bySubject = $identities->find($manifest->issuer, $entry->subject);
    $byUser = CRM_Core_DAO::singleValueQuery(
        'SELECT id FROM civicrm_oidc_sso_identity WHERE uf_match_id = %1',
        [1 => [$entry->userId, 'Integer']],
    );
    if ($bySubject !== null && $bySubject->userId !== $entry->userId) {
        throw new RuntimeException("Entry {$index}: subject is linked to another user.");
    }
    if ($byUser !== null && $byUser !== false && $bySubject === null) {
        throw new RuntimeException("Entry {$index}: user is linked to another subject.");
    }
    $currentRoles = $roles->get($entry->userId);
    if (array_diff($entry->managedRoleIds, $currentRoles) !== []) {
        throw new RuntimeException("Entry {$index}: managed_role_ids contains a role not held by the user.");
    }
    $prepared[] = [
        'entry' => $entry,
        'already_linked' => $bySubject !== null,
        'contact_id' => $account->contactId,
    ];
    printf(
        "%s entry=%d user_id=%d contact_id=%d roles=%s\n",
        $bySubject === null ? 'READY' : 'UNCHANGED',
        $index,
        $entry->userId,
        $account->contactId,
        implode(',', $entry->managedRoleIds),
    );
}

if (!$apply) {
    printf("DRY-RUN OK entries=%d; rerun with --apply to commit.\n", count($prepared));
    return;
}

$transaction->run(function () use ($prepared, $manifest, $linker, $roleMigrator): void {
    foreach ($prepared as $item) {
        $entry = $item['entry'];
        if (!$item['already_linked']) {
            $linker->link($manifest->issuer, $entry->subject, $entry->userId);
        }
        if ($entry->managedRoleIds !== []) {
            $roleMigrator->claimExisting($entry->userId, $entry->managedRoleIds);
        }
    }
});
printf("APPLIED entries=%d\n", count($prepared));
