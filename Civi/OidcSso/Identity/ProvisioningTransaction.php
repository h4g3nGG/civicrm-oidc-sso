<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

interface ProvisioningTransaction
{
    /** @template T @param callable(): T $operation @return T */
    public function run(callable $operation): mixed;
}
