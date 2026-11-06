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

    public function testManagedRoleUpgradeSchemaMatchesCurrentSchema(): void
    {
        $upgrade = file_get_contents(__DIR__ . '/../../upgrade/schema/1001_OidcSsoManagedRole.entityType.php');
        self::assertIsString($upgrade);
        self::assertStringContainsString("'table' => 'civicrm_oidc_sso_managed_role'", $upgrade);
        self::assertStringContainsString("'uf_match_id'", $upgrade);
        self::assertStringContainsString("'role_id'", $upgrade);
    }
}
