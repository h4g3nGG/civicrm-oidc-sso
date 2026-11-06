<?php

declare(strict_types=1);

namespace Civi\OidcSso\Tests\Unit\Civi;

use Civi\OidcSso\Civi\Api4Client;

final class FakeApi4Client implements Api4Client
{
    /** @var list<array{entity: string, action: string, parameters: array<string, mixed>}> */
    public array $calls = [];

    /** @var array<string, list<list<array<string, mixed>>>> */
    public array $responses = [];

    public function queue(string $entity, string $action, array $response): void
    {
        $this->responses[$entity . '.' . $action][] = $response;
    }

    public function call(string $entity, string $action, array $parameters): array
    {
        $this->calls[] = ['entity' => $entity, 'action' => $action, 'parameters' => $parameters];
        $key = $entity . '.' . $action;
        if (!isset($this->responses[$key])) {
            return [];
        }
        return array_shift($this->responses[$key]) ?? [];
    }
}
