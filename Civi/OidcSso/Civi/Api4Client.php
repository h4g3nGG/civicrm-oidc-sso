<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

interface Api4Client
{
    /** @param array<string, mixed> $parameters @return list<array<string, mixed>> */
    public function call(string $entity, string $action, array $parameters): array;
}
