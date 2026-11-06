<?php

declare(strict_types=1);

namespace Civi\OidcSso\Identity;

interface LocalAccountGateway
{
    public function create(UserProfile $profile): ExternalIdentity;

    public function update(ExternalIdentity $identity, UserProfile $profile): void;
}
