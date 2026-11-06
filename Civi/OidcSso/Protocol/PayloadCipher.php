<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

interface PayloadCipher
{
    public function encrypt(string $plaintext): string;

    public function decrypt(string $ciphertext): string;
}
