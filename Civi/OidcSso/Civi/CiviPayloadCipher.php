<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Protocol\PayloadCipher;
use RuntimeException;

final class CiviPayloadCipher implements PayloadCipher
{
    public function encrypt(string $plaintext): string
    {
        $ciphertext = \Civi::service('crypto.token')->encrypt($plaintext, 'CRED');
        if (!is_string($ciphertext) || !str_starts_with($ciphertext, chr(2) . 'CTK?')) {
            throw new RuntimeException('CIVICRM_CRED_KEYS must provide encrypted credential storage.');
        }
        return $ciphertext;
    }

    public function decrypt(string $ciphertext): string
    {
        return \Civi::service('crypto.token')->decrypt($ciphertext, 'CRED');
    }
}
