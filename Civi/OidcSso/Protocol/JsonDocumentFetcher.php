<?php

declare(strict_types=1);

namespace Civi\OidcSso\Protocol;

use Civi\OidcSso\Exception\OidcValidationException;
use JsonException;

final class JsonDocumentFetcher
{
    public function __construct(private readonly HttpTransport $transport)
    {
    }

    /** @return array<string, mixed> */
    public function fetch(string $url): array
    {
        $response = $this->transport->send(new HttpRequest(
            'GET',
            $url,
            ['Accept' => 'application/json'],
            '',
        ));
        if ($response->statusCode !== 200) {
            throw new OidcValidationException('OpenID Connect JSON endpoint returned an unexpected status.');
        }
        if (!$this->hasJsonContentType($response->headers)) {
            throw new OidcValidationException('OpenID Connect endpoint did not return JSON content.');
        }
        try {
            $document = json_decode($response->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new OidcValidationException('OpenID Connect endpoint returned invalid JSON.', 0, $exception);
        }
        if (!is_array($document) || array_is_list($document)) {
            throw new OidcValidationException('OpenID Connect JSON document must be an object.');
        }
        return $document;
    }

    /** @param array<string, string> $headers */
    private function hasJsonContentType(array $headers): bool
    {
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Content-Type') === 0) {
                return preg_match('#^application/(?:[A-Za-z0-9.-]+\+)?json(?:\s*;|$)#iD', trim($value)) === 1;
            }
        }
        return false;
    }
}
