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
}
