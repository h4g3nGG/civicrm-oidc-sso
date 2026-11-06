<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

interface ExternalIdentityRepository
{
    public function find(string $issuer, string $subject): ?ExternalIdentity;

    public function create(string $issuer, string $subject, ExternalIdentity $identity): void;
}
