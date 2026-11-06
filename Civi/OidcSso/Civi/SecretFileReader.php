<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use RuntimeException;

final class SecretFileReader
{
    public function read(string $path, string $description): string
    {
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException("{$description} file is not readable.");
        }
        $secret = file_get_contents($path);
        if ($secret === false) {
            throw new RuntimeException("{$description} file could not be read.");
        }
        $secret = rtrim($secret, "\r\n");
        if ($secret === '') {
            throw new RuntimeException("{$description} must not be empty.");
        }
        return $secret;
    }
}
