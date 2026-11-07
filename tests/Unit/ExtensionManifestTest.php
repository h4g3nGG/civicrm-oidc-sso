<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ExtensionManifestTest extends TestCase
{
    public function testLegacyCiviClassesUseDocumentedPsrZeroRoot(): void
    {
        $manifest = simplexml_load_file(__DIR__ . '/../../info.xml');
        self::assertNotFalse($manifest);
        self::assertSame('.', (string) $manifest->classloader->psr0['path']);
        self::assertSame('CRM_', (string) $manifest->classloader->psr0['prefix']);
    }

    public function testDirectoryMetadataIsPresent(): void
    {
        $manifest = simplexml_load_file(__DIR__ . '/../../info.xml');
        self::assertNotFalse($manifest);
        self::assertSame('module', (string) $manifest['type']);
        self::assertSame('oidc_sso', (string) $manifest['key']);
        self::assertNotSame('', (string) $manifest->maintainer->email);
        self::assertGreaterThanOrEqual(3, count($manifest->urls->url));
        $phpVersions = [];
        foreach ($manifest->php_compatibility->ver as $version) {
            $phpVersions[] = (string) $version;
        }
        self::assertSame(['8.1', '8.2', '8.3', '8.4', '8.5'], $phpVersions);
    }

    public function testUsesDocumentedSettingsAdministrationMixin(): void
    {
        $manifest = simplexml_load_file(__DIR__ . '/../../info.xml');
        self::assertNotFalse($manifest);
        $mixins = [];
        foreach ($manifest->mixins->mixin as $mixin) {
            $mixins[] = (string) $mixin;
        }
        self::assertContains('setting-admin@1.1.1', $mixins);
        self::assertSame(
            'OpenID Connect SSO Settings',
            (string) $manifest->civix->{'setting-page-title'},
        );
    }

    public function testAuditUpgradeSchemasAreFrozenSnapshots(): void
    {
        foreach (['OidcSsoAuditEvent', 'OidcSsoAuditHead'] as $entity) {
            $path = __DIR__ . "/../../upgrade/schema/1002_{$entity}.entityType.php";
            self::assertFileExists($path);
            $schema = (string) file_get_contents($path);
            self::assertStringContainsString("'name' => '{$entity}'", $schema);
            self::assertStringNotContainsString('return require', $schema);
        }
    }

    public function testAuditSchemaUpgradeIsRegistered(): void
    {
        $upgrader = (string) file_get_contents(__DIR__ . '/../../CRM/OidcSso/Upgrader.php');
        self::assertStringContainsString('function upgrade_1002()', $upgrader);
        self::assertStringContainsString('1002_OidcSsoAuditHead.entityType.php', $upgrader);
        self::assertStringContainsString('1002_OidcSsoAuditEvent.entityType.php', $upgrader);
        self::assertStringContainsString('function upgrade_1003()', $upgrader);
        self::assertStringContainsString('DROP FOREIGN KEY', $upgrader);
        self::assertStringContainsString('function upgrade_1004()', $upgrader);
        self::assertStringContainsString('RoleMappingSetting::canonicalJson', $upgrader);
    }

    public function testAuditUserReferenceIsNotMutableThroughAForeignKey(): void
    {
        $schema = (string) file_get_contents(__DIR__ . '/../../schema/OidcSsoAuditEvent.entityType.php');
        self::assertStringContainsString("'title' => 'Local User Reference'", $schema);
        self::assertStringNotContainsString("'entity_reference'", $schema);
    }

    public function testManagedRoleUpgradeSchemaMatchesCurrentSchema(): void
    {
        $upgrade = file_get_contents(__DIR__ . '/../../upgrade/schema/1001_OidcSsoManagedRole.entityType.php');
        self::assertIsString($upgrade);
        self::assertStringContainsString("'table' => 'civicrm_oidc_sso_managed_role'", $upgrade);
        self::assertStringContainsString("'uf_match_id'", $upgrade);
        self::assertStringContainsString("'role_id'", $upgrade);
    }
}
