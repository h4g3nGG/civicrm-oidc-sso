<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Protocol;

use Civi\OidcSso\Protocol\PayloadCipher;

final class ReversingPayloadCipher implements PayloadCipher
{
    public function encrypt(string $plaintext): string
    {
        return strrev($plaintext);
    }

    public function decrypt(string $ciphertext): string
    {
        return strrev($ciphertext);
    }
}
