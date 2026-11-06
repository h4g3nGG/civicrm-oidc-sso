<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

final class CiviApi4Client implements Api4Client
{
    public function call(string $entity, string $action, array $parameters): array
    {
        $parameters['checkPermissions'] = false;
        /** @var list<array<string, mixed>> $records */
        $records = civicrm_api4($entity, $action, $parameters)->getArrayCopy();
        return $records;
    }
}
