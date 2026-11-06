<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Civi;

use Civi\OidcSso\Civi\SecretFileReader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class SecretFileReaderTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'oidc-secret-');
        self::assertIsString($path);
        $this->path = $path;
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }

    public function testReadsSecretAndRemovesOnlyTrailingNewline(): void
    {
        file_put_contents($this->path, "  secret value  \n");
        self::assertSame('  secret value  ', (new SecretFileReader())->read($this->path, 'test secret'));
    }

    public function testRejectsEmptySecret(): void
    {
        file_put_contents($this->path, "\r\n");
        $this->expectException(RuntimeException::class);
        (new SecretFileReader())->read($this->path, 'test secret');
    }
}
