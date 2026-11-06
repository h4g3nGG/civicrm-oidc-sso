<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

interface HttpTransport
{
    public function send(HttpRequest $request): HttpResponse;
}
