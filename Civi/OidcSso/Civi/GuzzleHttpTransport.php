<?php

declare(strict_types=1);

namespace Civi\OidcSso\Civi;

use Civi\OidcSso\Exception\OidcValidationException;
use Civi\OidcSso\Protocol\HttpRequest;
use Civi\OidcSso\Protocol\HttpResponse;
use Civi\OidcSso\Protocol\HttpTransport;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

final class GuzzleHttpTransport implements HttpTransport
{
    public function __construct(
        private readonly ClientInterface $client,
        private readonly int $maximumResponseBytes = 1048576,
        private readonly float $connectTimeoutSeconds = 5.0,
        private readonly float $timeoutSeconds = 10.0,
    ) {
        if ($maximumResponseBytes < 1024 || $maximumResponseBytes > 10485760) {
            throw new InvalidArgumentException('HTTP response limit must be between 1 KiB and 10 MiB.');
        }
    }

    public function send(HttpRequest $request): HttpResponse
    {
        $this->validateRequest($request);
        try {
            $response = $this->client->request($request->method, $request->url, [
                'headers' => $request->headers,
                'body' => $request->body,
                'http_errors' => false,
                'allow_redirects' => false,
                'connect_timeout' => $this->connectTimeoutSeconds,
                'timeout' => $this->timeoutSeconds,
                'verify' => true,
                'stream' => true,
            ]);
        } catch (GuzzleException $exception) {
            throw new OidcValidationException('OpenID Connect HTTP request failed.', 0, $exception);
        }

        try {
            $body = $this->readLimitedBody($response->getBody());
        } catch (OidcValidationException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            throw new OidcValidationException('OpenID Connect HTTP response could not be read.', 0, $exception);
        }
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }
        return new HttpResponse($response->getStatusCode(), $headers, $body);
    }

    private function validateRequest(HttpRequest $request): void
    {
        if (!in_array($request->method, ['GET', 'POST'], true)) {
            throw new OidcValidationException('Unsupported OpenID Connect HTTP method.');
        }
        $parts = parse_url($request->url);
        if (
            $parts === false
            || ($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['fragment'])
        ) {
            throw new OidcValidationException('OpenID Connect HTTP URL must be an absolute HTTPS URL.');
        }
        foreach ($request->headers as $name => $value) {
            if (
                preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/D', $name) !== 1
                || str_contains($value, "\r")
                || str_contains($value, "\n")
            ) {
                throw new OidcValidationException('OpenID Connect HTTP header is invalid.');
            }
        }
    }

    private function readLimitedBody(StreamInterface $stream): string
    {
        $body = '';
        while (!$stream->eof()) {
            $remaining = $this->maximumResponseBytes + 1 - strlen($body);
            if ($remaining <= 0) {
                throw new OidcValidationException('OpenID Connect HTTP response exceeds the size limit.');
            }
            $body .= $stream->read(min(8192, $remaining));
        }
        if (strlen($body) > $this->maximumResponseBytes) {
            throw new OidcValidationException('OpenID Connect HTTP response exceeds the size limit.');
        }
        return $body;
    }
}
