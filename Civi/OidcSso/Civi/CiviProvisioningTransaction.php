<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Identity\ProvisioningTransaction;
use Throwable;

final class CiviProvisioningTransaction implements ProvisioningTransaction
{
    public function run(callable $operation): mixed
    {
        $transaction = new \CRM_Core_Transaction();
        try {
            $result = $operation();
            $transaction->commit();
            return $result;
        } catch (Throwable $exception) {
            $transaction->rollback();
            throw $exception;
        }
    }
}
